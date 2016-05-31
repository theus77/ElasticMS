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
    	$dateInterval = new \DateInterval("PT1M");//Interval of 1 minutes
		/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
    	$contentTypeNameFrom = $input->getArgument('contentTypeNameFrom');
    	$contentTypeNameTo = $input->getArgument('contentTypeNameTo');
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
		$contentTypeTo = $contentTypeRepository->findOneBy(array("name" => $contentTypeNameTo));
		//TODO Verify $contentType
		//TODO Verify $elasticsearchIndex
//		dump($contentType);
		/** @var \AppBundle\Entity\FieldType $fieldType */
		$fieldType = $contentTypeTo->getFieldType();
//		dump($fieldType);
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
				$revision = $revisionRep->findOneBy([
						'ouuid' => $value["_id"],
						'endTime' => null,
						'contentType' => $contentTypeTo,
				]);
				
				$now = new \DateTime();
				if(isset($revision)){
					$newRevision = new Revision($revision);
					$revision->setEndTime($now);
					$revision->setLockBy("SYSTEM_MIGRATE");
					$revision->setLockUntil($now->add($dateInterval));//Lock for 1 minutes
					$em->persist($revision);
				} else {
					$newRevision = new Revision();
					$newRevision->setOuuid($value["_id"]);
				}
				$newRevision->setContentType($contentTypeTo);
				$newRevision->setDraft(true);
				$newRevision->setStartTime($now);
				$newRevision->setLockBy("SYSTEM_MIGRATE");
				$newRevision->setLockUntil($now->add($dateInterval));//Lock for 1 minutes
				
				$data = new DataField();
				$data->setFieldType($newRevision->getContentType()->getFieldType());
				$data->setRevisionId($newRevision->getId());
				$data->setOrderKey($newRevision->getContentType()->getFieldType()->getOrderKey());
				$newRevision->setDataField($data);
				
				$newRevision->getDataField()->updateDataStructure($newRevision->getContentType()->getFieldType());

				$data->updateDataValue($value["_source"]);
				$em->persist($newRevision);
				$em->flush($newRevision);
// 				break;
			}
// 			break;
		}
// 		dump($this->client->search([
// 				'index' => $elasticsearchIndex,
// 				'type' => $contentTypeName
// 		]));
		$output->writeln("Migration done");
    }
}