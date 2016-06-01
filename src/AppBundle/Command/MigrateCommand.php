<?php

// src/AppBundle/Command/GreetCommand.php
namespace AppBundle\Command;

use AppBundle\Entity\ContentType;
use AppBundle\Entity\DataField;
use AppBundle\Entity\Revision;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;
use AppBundle\Service\DataService;
use AppBundle\Exception\NotLockedException;

class MigrateCommand extends ContainerAwareCommand
{
	protected  $client;
	protected $mapping;
	protected $doctrine;
	protected $logger;
	protected $container;
	/**@var DataService $dataService*/
	protected $dataService;
	
	public function __construct(Registry $doctrine, Logger $logger, Client $client, $mapping, $container, DataService $dataService)
	{
		$this->doctrine = $doctrine;
		$this->logger = $logger;
		$this->client = $client;
		$this->mapping = $mapping;
		$this->container = $container;
		$this->dataService = $dataService;
		parent::__construct();
	}
	
	protected function configure()
    {
    	$this
            ->setName('ems:contenttype:migrate')
            ->setDescription('Migrate a content type from an elasticsearch index')
            ->addArgument(
                'contentTypeNameFrom',
                InputArgument::REQUIRED,
                'Content type name to migrate from'
            )
            ->addArgument(
                'contentTypeNameTo',
                InputArgument::REQUIRED,
                'Content type name to migrate into'
            )
            ->addArgument(
                'elasticsearchIndex',
                InputArgument::REQUIRED,
                'Elasticsearch index where to find ContentType objects as new source'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Allow to import from the default environment'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	$dateInterval = new \DateInterval("PT1M");//Interval of 1 minutes
		/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
    	$contentTypeNameFrom = $input->getArgument('contentTypeNameFrom');
    	$contentTypeNameTo = $input->getArgument('contentTypeNameTo');
    	$elasticsearchIndex = $input->getArgument('elasticsearchIndex');
		$output->writeln("Start migration");
		
		/** @var \AppBundle\Repository\ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
		/** @var \AppBundle\Entity\ContentType $contentTypeTo */
		$contentTypeTo = $contentTypeRepository->findOneBy(array("name" => $contentTypeNameTo, 'deleted' => false));
		
		if(!$contentTypeTo) {
			$output->writeln("<error>Content type not found</error>");
			exit;
		}
		if(!$input->getOption('force') && strcmp($contentTypeTo->getEnvironment()->getAlias(), $elasticsearchIndex) === 0 && strcmp($contentTypeNameFrom, $contentTypeNameTo) === 0) {
			$output->writeln("<error>You can not import a content type on himself</error>");
			exit;
		}
		
		$arrayElasticsearchIndex = $this->client->search([
				'index' => $elasticsearchIndex,
				'type' => $contentTypeNameFrom,
				'size' => 1
		]);
		
		$total = $arrayElasticsearchIndex["hits"]["total"];
		for($from = 0; $from < $total; $from = $from + 50) {
			$arrayElasticsearchIndex = $this->client->search([
					'index' => $elasticsearchIndex,
					'type' => $contentTypeNameFrom,
					'size' => 50,
					'from' => $from
			]);
			$output->writeln("Migrating " . ($from+1) . " / " . $total );
			foreach ($arrayElasticsearchIndex["hits"]["hits"] as $index => $value) {
				try{
					$newRevision = $this->dataService->initNewDraft($contentTypeNameTo, $value["_id"], NULL, "SYSTEM_MIGRATE");
					$data = new DataField();
					$data->setFieldType($newRevision->getContentType()->getFieldType());
					$data->setRevisionId($newRevision->getId());
					$data->setOrderKey(0);//0==$newRevision->getContentType()->getFieldType()->getOrderKey()
					$newRevision->setDataField($data);
					
					$newRevision->getDataField()->updateDataStructure($newRevision->getContentType()->getFieldType());
	
					$data->updateDataValue($value["_source"]);
					//Finalize draft
					$newRevision = $this->dataService->finalizeDraft($newRevision, "SYSTEM_MIGRATE");
					$output->writeln(".");
				}
				catch(NotLockedException $e){
					$output->writeln("<error>'.$e.'</error>");
				}
			}
		}
		$output->writeln("\nMigration done");
    }
}