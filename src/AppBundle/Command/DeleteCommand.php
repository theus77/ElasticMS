<?php

// src/AppBundle/Command/GreetCommand.php
namespace AppBundle\Command;

use AppBundle\Entity\ContentType;
use AppBundle\Repository\ContentTypeRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Repository\RevisionRepository;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use AppBundle\Repository\NotificationRepository;

class DeleteCommand extends ContainerAwareCommand
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
            ->setName('ems:contenttype:delete')
            ->setDescription('Delete all instances of a content type ')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Content type name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
		/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
		/** @var  Client $client */
		$client = $this->client;
		$name = $input->getArgument('name');
		/** @var ContentTypeRepository $ctRepo */
		$ctRepo = $em->getRepository('AppBundle:ContentType');
		/** @var ContentType $contentType */
		$contentType = $ctRepo->findOneBy([
				'name' => $name, 
				'deleted'=> 0
				
		]);
		if($contentType){
			$output->writeln("Content type found");		
			/** @var RevisionRepository $revRepo */
			$revRepo = $em->getRepository('AppBundle:Revision');
			
			/** @var NotificationRepository $notRepo */
			$notRepo = $em->getRepository('AppBundle:Notification');
			
			$counter = 0;
			if($revRepo->countByContentType($contentType) == 0) {
				$output->writeln("Content type \"".$name."\" already empty");
			} else {
				while($revRepo->countByContentType($contentType) > 0 ) {
					$revisions = $revRepo->findByContentType($contentType, null, 20);
					/**@var \AppBundle\Entity\Revision $revision */
					foreach ($revisions as $revision){
						foreach($revision->getEnvironments() as $environment) {
							try{
								$client->delete([
										'index' => $environment->getAlias(),
										'type' => $contentType->getName(),
										'id' => $revision->getOuuid(),
								]);
							} catch (Missing404Exception $e) {
								//Deleting something that is not present shouldn't make problem.
							}
							$revision->removeEnvironment($environment);
						}
						++$counter;
						$notifications = $notRepo->findBy([
							'revisionId' => $revision,
						]);
						foreach ($notifications as $notification){
							$em->remove($notification);
						}
						
						$em->remove($revision);
							
						$output->write('.');
						$em->flush();
// 						$em->clear($revision);
					}
					
					unset($revisions);
					$output->writeln("\n".$counter. ' documents have been deleted so far');
				}
			}
			
		} else {
				$output->writeln("Content type ".$name." not found");
		}
		
		
    }
    


}