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
use AppBundle\Entity\ContentType;
use AppBundle\Service\ContentTypeService;
use AppBundle\Service\EnvironmentService;
use AppBundle\Service\PublishService;

class AlignCommand extends ContainerAwareCommand
{
	protected $doctrine;
	protected $logger;
	protected $client;
	protected $data;
	/**@var ContentTypeService */
	private $contentTypeService;
	/**@var EnvironmentService */
	private $environmentService;
	/**@var PublishService */
	private $publishService;
	
	public function __construct(Registry $doctrine, Logger $logger, Client $client, DataService $data, ContentTypeService $contentTypeService, EnvironmentService $environmentService, PublishService $publishService)
	{
		$this->doctrine = $doctrine;
		$this->logger = $logger;
		$this->client = $client;
		$this->data = $data;
		$this->contentTypeService = $contentTypeService;
		$this->environmentService = $environmentService;
		$this->publishService = $publishService;
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
    	if(! $input->getOption('force')){
			$output->writeln('<error>Has protection, the force option is mandatory</error>');
			return -1;
		}   	
		
		$sourceName = $input->getArgument('source');
    	$targetName = $input->getArgument('target');

    	$source = $this->environmentService->getAliasByName($sourceName);
    	$target = $this->environmentService->getAliasByName($targetName);
    	
    	if(!$source){
    		$output->writeln('<error>Source '.$sourceName.' not found</error>');
    	}   	
    	if(!$target){
    		$output->writeln('<error>Target '.$targetName.' not found</error>');
    	}
    	
    	if(! $source || ! $target){
			return -1;
		}
		
		if($source === $target) {
			$output->writeln('<error>Target and source are the same environment, it\'s aligned ;-)</error>');
			return -1;
		}
		
		$this->logger->info('Execute the AlignCommand');
    	 
    	$total = $this->client->search([
    		'index' => $source->getAlias(),
    		'size' => 0,
    	])['hits']['total'];
    	
		$output->writeln('The source environment contains '.$total.' elements, start aligning environments...');

    	// create a new progress bar
    	$progress = new ProgressBar($output, $total);
    	// start and displays the progress bar
    	$progress->start();
    	
    	$deletedRevision = 0;
    	$alreadyAligned = 0;
    	$targetIsPreviewEnvironment = [];
    	 
    	for($from = 0; $from < $total; $from = $from + 50) {
    		$scroll = $this->client->search([
    			'index' => $source->getAlias(),
    			'size' => 50,
    			'from' => $from,
    			'preference' => '_primary', //http://stackoverflow.com/questions/10836142/elasticsearch-duplicate-results-with-paging
    		]);
    		
    		foreach ($scroll['hits']['hits'] as &$hit){
    			$revision = $this->data->getRevisionByEnvironment($hit['_id'], $this->contentTypeService->getByName($hit['_type']), $source);
    			if($revision->getDeleted()){
    				++$deletedRevision;
    			}
    			else if($revision->getContentType()->getEnvironment() === $target){
					if(!isset($targetIsPreviewEnvironment[$revision->getContentType()->getName()])){
						$targetIsPreviewEnvironment[$revision->getContentType()->getName()] = 0;
					}
					++$targetIsPreviewEnvironment[$revision->getContentType()->getName()];
    			}
    			else {
	    			if($this->publishService->publish($revision, $target, true) == 0){
	    				++ $alreadyAligned;
	    			}
    			}
    			$progress->advance();    				
    		}
    	}
    	
    	$progress->finish();
    	$output->writeln('');
    	if($deletedRevision){
			$output->writeln('<error>'.$deletedRevision.' deleted revisions were not aligned</error>');
		}
		if($alreadyAligned){
			$output->writeln(''.$alreadyAligned.' revisions were already aligned');
		}
		foreach ($targetIsPreviewEnvironment as $ctName => $counter){
			$output->writeln('<error>'.$counter.' '.$ctName.' revisions were not aligned as '.$targetName.' is the default environment</error>');				
		}
		
		$output->writeln('Environments are aligned.');
    	return 0;    
    }
}