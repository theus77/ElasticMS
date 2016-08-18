<?php

// src/AppBundle/Command/GreetCommand.php
namespace AppBundle\Command;

use AppBundle\Entity\Environment;
use AppBundle\Repository\JobRepository;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Console\Helper\ProgressBar;
use AppBundle\Entity\Revision;
use AppBundle\Repository\RevisionRepository;
use AppBundle\Service\DataService;

class AlignCommand extends ContainerAwareCommand
{
	protected $doctrine;
	protected $logger;
	protected $client;
	protected $data;
	
	public function __construct(Registry $doctrine, Logger $logger, Client $client, DataService $data)
	{
		$this->doctrine = $doctrine;
		$this->logger = $logger;
		$this->client = $client;
		$this->data = $data;
		parent::__construct();
	}
	
    protected function configure()
    {
    	$this->logger->info('Configure the AlignCommand');
        $this
            ->setName('ems:environment:align')
            ->setDescription('Align an environment from another one')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Environment source name'
            )
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Environment target name'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'If set, the task will be performed (protection)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {	
    	/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
		/** @var JobRepository $envRepo */
		$envRepo = $em->getRepository('AppBundle:Environment');
		/** @var RevisionRepository $revRepo */
		$revRepo = $em->getRepository('AppBundle:Revision');
		
    	$this->logger->info('Execute the AlignCommand');
    	
    	$sourceName = $input->getArgument('source');
		/** @var Environment $environment */
		$source = $envRepo->findOneBy(['name' => $sourceName, 'managed' => true]);

		if(!$source){
			$output->writeln('<error>Source '.$sourceName.' not found</error>');
		}
    	
    	$targetName = $input->getArgument('target');
		/** @var Environment $environment */
		$target = $envRepo->findOneBy(['name' => $targetName, 'managed' => true]);

		if(!$target){
			$output->writeln('<error>Target '.$targetName.' not found</error>');
		}
		
		if(! $source || ! $target){
			return -1;
		}
		
		if($source === $target) {
			$output->writeln('<error>Target and source are the same environment, it\'s aligned ;-)</error>');
			return 0;
		}
		
		if(! $input->getOption('force')){
			$output->writeln('<error>Has protection, the force option is mandatory</error>');
			return -1;
		}
			
		$output->writeln('The source environment contains '.$source->getRevisions()->count().' elements, start aligning environments...');

		// create a new progress bar
		$progress = new ProgressBar($output, $source->getRevisions()->count());
		// start and displays the progress bar
		$progress->start();

		$deletedRevision = 0;
		$targetIsPreviewEnvironment = [];
		$alreadyAligned = 0;
		$lockedRevision = 0;
		
		/**@var Revision $revision*/
		foreach ($source->getRevisions() as $revision) {
			if(!$revision->getDeleted() && !$revision->getContentType()->getDeleted()){
				if($revision->getContentType()->getEnvironment() === $target){
					if(!isset($targetIsPreviewEnvironment[$revision->getContentType()->getName()])){
						$targetIsPreviewEnvironment[$revision->getContentType()->getName()] = 0;
					}
					++$targetIsPreviewEnvironment[$revision->getContentType()->getName()];
				}
				else {
					if($revision->getEnvironments()->contains($target)){
						++$alreadyAligned;
					}
					else {
						
						$now = new \DateTime();
						$until = $now->add(new \DateInterval("PT5M"));//+5 minutes
						
						/**@var Revision $previousRev*/
						$previousRev = $revRepo->findByOuuidContentTypeAndEnvironnement($revision, $target);
						if($previousRev && count($previousRev) == 1){
							$previousRev = $previousRev[0];
							$previousRev->setLockBy('SYSTEM_ALIGN');
							$previousRev->setLockUntil($until);
							$previousRev->removeEnvironment($target);
							$em->persist($previousRev);

						}

						$revision->setLockBy('SYSTEM_ALIGN');
						$revision->setLockUntil($until);
						$revision->addEnvironment($target);
						$em->persist($revision);
						$this->client->index([
							'index' => $target->getAlias(),
							'type' => $revision->getContentType()->getName(),
							'id' => $revision->getOuuid(),
							'body' => $revision->getRawData(),
						]);
						$em->flush();
					}
				}
			}
			else {
				++ $deletedRevision;
			}
			
			// advance the progress bar 1 unit
			$progress->advance();
    	}
		// ensure that the progress bar is at 100%
		$progress->finish();
		$output->writeln('');
		if($deletedRevision){
			$output->writeln('<error>'.$deletedRevision.' deleted revisions were not aligned</error>');
		}
		if($alreadyAligned){
			$output->writeln(''.$alreadyAligned.' revisions were already aligned');
		}
		if(count($targetIsPreviewEnvironment)){
			foreach ($targetIsPreviewEnvironment as $ctName => $counter){
				$output->writeln('<error>'.$counter.' '.$ctName.' revisions were not aligned as '.$targetName.' is the default environment</error>');				
			}
		}

		$output->writeln('Environments are aligned.');
    }
}