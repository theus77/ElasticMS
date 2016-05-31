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

class MigrateCommand extends ContainerAwareCommand
{
	protected  $client;
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
    	$this
            ->setName('ems:contenttype:migrate')
            ->setDescription('Migrate a content type from an elasticsearch index')
            ->addArgument(
                'contentTypeName',
                InputArgument::REQUIRED,
                'Content type name to migrate into'
            )
            ->addArgument(
                'elasticsearchIndex',
                InputArgument::OPTIONAL,
                'Elasticsearch index where to find ContentType objects as new source'
            )
            ->addOption(
                'purge',
                null,
                InputOption::VALUE_NONE,
                'If set, all previous revisions will be deleted from the database'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
    	$contentTypeName = $input->getArgument('contentTypeName');
    	$elasticsearchIndex = $input->getArgument('elasticsearchIndex');
		$output->writeln("Start migration");
		if($input->getOption('purge')) {
			$output->writeln("All previous revision will be purged");
		}
		/** @var RevisionRepository $revisionRep */
		$revisionRep = $em->getRepository('AppBundle:Revision');
		/** @var \AppBundle\Repository\ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
		/** @var \AppBundle\Entity\ContentType $contentType */
		$contentType = $contentTypeRepository->findOneBy(array("name" => $contentTypeName));
		//TODO Verify $contentType
		//TODO Verify $elasticsearchIndex
		//		dump($contentType);
		/** @var \AppBundle\Entity\FieldType $fieldType */
		$fieldType = $contentType->getFieldType();
//		dump($fieldType);
		$arrayElasticsearchIndex = $this->client->search([
				'index' => $elasticsearchIndex,
				'type' => $contentTypeName,
				'size' => 1
		]);
		
		$total = $arrayElasticsearchIndex["hits"]["total"];
		dump($arrayElasticsearchIndex["hits"]["total"]);
		for($from = 0; $from < $total; $from = $from + 50) {
			$arrayElasticsearchIndex = $this->client->search([
					'index' => $elasticsearchIndex,
					'type' => $contentTypeName,
					'size' => 50,
					'from' => $from
			]);
			dump("" );
			dump("Migrating " . $from . " / " . $total );
			dump("");
			foreach ($arrayElasticsearchIndex["hits"]["hits"] as $index => $value) {
				$revision = $revisionRep->findOneBy([
						'ouuid' => $value["_id"],
						'endTime' => null
				]);
				
				$now = new \DateTime();
				if(isset($revision)){
					$newRevision = new Revision($revision);
					$newRevision->setStartTime($now);
					$revision->setEndTime($now);
					
					$em->persist($revision);
				} else {
					$newRevision = new Revision();
					$newRevision->setOuuid($value["_id"]);
					$newRevision->setContentType($contentType);
					$newRevision->setLockBy("SYSTEM_MIGRATE");
					$newRevision->setStartTime($now);
					$newRevision->setDraft(false);
				}
				$firstDataField = new DataField();
				$firstDataField->setFieldType($contentType->getFieldType());
				$firstDataField->setRevisionId($newRevision->getId());
				$firstDataField->setOrderKey($contentType->getFieldType()->getOrderKey());
				$firstDataField->updateDataStructure($contentType->getFieldType());
				$firstDataField->updateDataValue($value["_source"]);
// 				$revision->getDataField()->updateDataValue($value);
				$newRevision->setDataField($firstDataField);
				$em->persist($newRevision);
//				dump($newRevision);
// 				break;
			}
			$em->flush();
// 			break;
		}
// 		dump($this->client->search([
// 				'index' => $elasticsearchIndex,
// 				'type' => $contentTypeName
// 		]));
		$output->writeln("Migration done");
    }
}