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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Repository\FieldTypeRepository;
use AppBundle\Repository\RevisionRepository;
use AppBundle\Repository\TemplateRepository;
use AppBundle\Repository\ViewRepository;

class CleanDeletedContentTypeCommand extends ContainerAwareCommand
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
            ->setName('ems:contenttype:clean')
            ->setDescription('Clean all deleted content types');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
		/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
		/** @var  Client $client */
		$client = $this->client;
		/** @var ContentTypeRepository $ctRepo */
		$ctRepo = $em->getRepository('AppBundle:ContentType');
		/** @var FieldTypeRepository $fieldRepo */
		$fieldRepo = $em->getRepository('AppBundle:FieldType');
		/** @var RevisionRepository $revisionRepo */
		$revisionRepo = $em->getRepository('AppBundle:Revision');
		/** @var TemplateRepository $templateRepo */
		$templateRepo = $em->getRepository('AppBundle:Template');
		/** @var ViewRepository $viewRepo */
		$viewRepo = $em->getRepository('AppBundle:View');
		
		
		$output->writeln('Cleaning deleted fields');
		$fields = $fieldRepo->findBy(['deleted' => true]);
		foreach ($fields as $field){
			$em->remove($field);
		}
		$em->flush();
		
		/** @var ContentType $contentType */
		$contentTypes = $ctRepo->findBy([
				'deleted'=> true	
		]);
		
		
		foreach ($contentTypes as $contentType) {
			
			
			$output->writeln('Remove deleted content type '.$contentType->getName());
			//remove field types
			if($contentType->getFieldType()){
				$contentType->unsetFieldType();
				$em->persist($contentType);
			}
			$em->flush($contentType);
			$fields = $fieldRepo->findBy([
				'contentType'=> $contentType	
			]);
			
			$output->writeln('Remove '.count($fields).' assosiated fields');			
			foreach ($fields as $field){
				$em->remove($field);
				$em->flush($field);
			}

			$revisions = $revisionRepo->findBy(['contentType' => $contentType]);
			$output->writeln('Remove '.count($revisions).' assosiated revisions');
			foreach ($revisions as $revision){
				$em->remove($revision);
				$em->flush($revision);
			}

			$templates = $templateRepo->findBy(['contentType' => $contentType]);
			$output->writeln('Remove '.count($templates).' assosiated templates');
			/**@var \AppBundle\Entity\Template $template*/
			foreach ($templates as $template){
				$em->remove($template);
				$em->flush($template);
			}
			
			$views = $viewRepo->findBy(['contentType' => $contentType]);
			$output->writeln('Remove '.count($views).' assosiated views');
			foreach ($views as $view){
				$em->remove($view);
				$em->flush($view);
			}
			
			
			$em->remove($contentType);
			$em->flush($contentType);		
		}	
    }
    


}