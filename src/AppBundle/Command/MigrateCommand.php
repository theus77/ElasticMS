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
use AppBundle\Exception\NotLockedException;
use AppBundle\Repository\RevisionRepository;
use AppBundle\Service\Mapping;

class MigrateCommand extends ContainerAwareCommand
{
	protected $client;
	/**@var Mapping */
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
    	if($contentTypeTo->getDirty()) {
			$output->writeln("<error>Content type \"".$contentTypeNameTo."\" is dirty. Please clean it first</error>");
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
		
		for($from = 0; $from < $total; $from = $from + 10) {
			$arrayElasticsearchIndex = $this->client->search([
					'index' => $elasticsearchIndex,
					'type' => $contentTypeNameFrom,
					'size' => 10,
					'from' => $from
			]);
			$output->writeln("\nMigrating " . ($from+1) . " / " . $total );

			/** @var RevisionRepository $repository */
			$repository = $em->getRepository ( 'AppBundle:Revision' );

			foreach ($arrayElasticsearchIndex["hits"]["hits"] as $index => $value) {
//				dump($value);
				try{
					$now = new \DateTime();
					
					$newRevision = $repository->insertRevision($contentTypeTo, $value['_id'], $now, $value['_source']);
// 					$newRevision = new Revision();
// 					$newRevision->setContentType($contentTypeTo);
// 					$newRevision->setDeleted(false);
// 					$newRevision->setDraft(true);
// 					$newRevision->setOuuid($value['_id']);
// 					$newRevision->setLockBy("SYSTEM_MIGRATE");
// 					$newRevision->setLockUntil(new \DateTime("+5 minutes"));
					

// 					$data = new DataField();
// 					$data->setFieldType($newRevision->getContentType()->getFieldType());
// 					$data->setRevisionId($newRevision->getId());
// 					$data->setOrderKey(0);//0==$newRevision->getContentType()->getFieldType()->getOrderKey()
// 					$newRevision->setDataField($data);					
// 					$newRevision->getDataField()->updateDataStructure($newRevision->getContentType()->getFieldType());
// 					$data->updateDataValue($value["_source"]);
					
 					$repository->finaliseRevision($contentTypeTo, $value['_id'], $now);
// 					$newRevision->setStartTime($now);
					
// 					$em->persist($newRevision);
//					$object = $this->mapping->dataFieldToArray($newRevision->getDataField());

					$this->client->index([
							'index' => $contentTypeTo->getEnvironment()->getAlias(),
							'type' => $contentTypeNameTo,
							'id' => $value['_id'],
							'body' => $value['_source'],
					]);
					//TODO: Test if client->index OK
					$repository->publishRevision($newRevision);
					//TODO: Improvement : http://symfony.com/doc/current/components/console/helpers/progressbar.html
					$output->write(".");
 					$em->flush($newRevision);
 					$em->clear($newRevision);
// 					unset($newRevision);
// 					unset($object);
// 					unset($arrayElasticsearchIndex);

					//hot fix query: insert into `environment_revision`  select id, 1 from `revision` where `end_time` is null;
				}
				catch(NotLockedException $e){
					$output->writeln("<error>'.$e.'</error>");
				}
			}
			$repository->clear();
		}
		$output->writeln("Migration done");
    }
}