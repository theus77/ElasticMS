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

class ReindexCommand extends ContainerAwareCommand
{
	protected $client;
	protected $mapping;
	protected $doctrine;
	protected $logger;
	protected $container;
	
	public function __construct(Registry $doctrine, Logger $logger, Client $client, $mapping, $container)
	{
		$this->doctrine = $doctrine;
		$this->logger = $logger;
		$this->client = $client;
		$this->mapping = $mapping;
		$this->container = $container;
		parent::__construct();
	}
	
    protected function configure()
    {
    	$this->logger->info('Configure the ReindexCommand');
        $this
            ->setName('ems:environment:reindex')
            ->setDescription('Reindex an environment in it\'s existing index')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Environment name'
            )
            ->addArgument(
                'index',
                InputArgument::OPTIONAL,
                'Elasticsearch index where to index environment objects'
            )
//             ->addOption(
//                 'force',
//                 null,
//                 InputOption::VALUE_NONE,
//                 'If set, the task will yell in uppercase letters'
//             )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {	
    	$this->logger->info('Execute the ReindexCommand');
    	$name = $input->getArgument('name');
    	/** @var EntityManager $em */
		$em = $this->doctrine->getManager();

		/** @var JobRepository $envRepo */
		$envRepo = $em->getRepository('AppBundle:Environment');
		/** @var Environment $environment */
		$environment = $envRepo->findBy(['name' => $name, 'managed' => true]);
		if($environment && count($environment) == 1) {
			$environment = $environment[0];
			
			$index = $input->getArgument('index');
			if(!$index) {
				$index = $environment->getAlias();
			}
			
			$count = 0;
			$deleted = 0;
			$error = 0;
			/** @var \AppBundle\Entity\Revision $revision */
			foreach ($environment->getRevisions() as $revision) {
				if(!$revision->getDeleted() && !$revision->getContentType()->getDeleted() && $revision->getEndTime() == null){
					$status = $this->client->index([
							'index' => $index,
							'id' => $revision->getOuuid(),
							'type' => $revision->getContentType()->getName(),
							'body' => $revision->getRawData()
					]);
					if($status["_shards"]["failed"] == 1) {
						$error++;
					} else {
						$count++;				
					}
				} else {
					$deleted++;
				}
			}

			$output->writeln($count.' objects have been reindexed in '.$index.' ('.$deleted.' not indexed, '.$error.' with indexing error)');
			
			$output->writeln('<comment>Elasticsearch is indexing...</comment>');
			$counter = $this->client->count(['index' => $index])['count'];
			$counterAlert = 0;
			while($counter < $count){
				$output->write('.');
				sleep(1);
				//Detection of infinite loop
				$newCounter = $this->client->count(['index' => $index])['count'];
				if($counter == $newCounter){
					$counterAlert++;
				} else {
					$counter = $newCounter;
				}
				if($counterAlert == 10) {
					$output->writeln('');
					$output->writeln('<error>Infinit loop!! '.$this->client->count(['index' => $index])['count'].'/'.$count.'</error>');
					break;
				}
				
			}
			$output->writeln('');
			$output->writeln('<comment>Done!</comment>');
		}
		else{
			$output->writeln("WARNING: Environment named ".$name." not found");
		}
    }
}