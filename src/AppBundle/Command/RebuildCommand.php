<?php

// src/AppBundle/Command/GreetCommand.php
namespace AppBundle\Command;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle\Entity\Environment;
use AppBundle\Repository\JobRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Elasticsearch\Client;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Monolog\Logger;

class RebuildCommand extends ContainerAwareCommand
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
            ->setName('ems:environment:rebuild')
            ->setDescription('Rebuild an environment in a brand new index')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Environment name'
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
        
		/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
		/** @var  Client $client */
		$client = $this->client;
		$name = $input->getArgument('name');
		/** @var JobRepository $envRepo */
		$envRepo = $em->getRepository('AppBundle:Environment');
		/** @var Environment $environment */
		$environment = $envRepo->findBy(['name' => $name, 'managed' => true]);
		if($environment && count($environment) == 1) {
			$environment = $environment[0];
			$indexName = $environment->getAlias().AppController::getFormatedTimestamp();
				
				
			/** @var \AppBundle\Repository\ContentTypeRepository $contentTypeRepository */
			$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
			$contentTypes = $contentTypeRepository->findAll();
			/** @var ContentType $contentType */
			
	
			$client->indices()->create([
					'index' => $indexName,
					'body' => ContentType::getIndexAnalysisConfiguration(),
			]);
			$output->writeln('A new index '.$indexName.' has been created');
			
			$mapping = [];
			
			/** @var ContentType $contentType */
			foreach ($contentTypes as $contentType){
				if($contentType->getEnvironment()->getManaged() && !$contentType->getDeleted()){
					try {
						$out = $client->indices ()->putMapping ( [
								'index' => $indexName,
								'type' => $contentType->getName (),
								'body' => $this->mapping->generateMapping ($contentType)
						] );
						$output->writeln('A new mapping for '.$contentType->getName ().' has been defined');					
					}
					catch (BadRequest400Exception $e){
						$output->writeln('ERROR: Error on putting mapping for '.$contentType->getName ().'!  Message: '.$e->getMessage());
						$output->writeln('ERROR: '. print_r($this->mapping->generateMapping ($contentType), true));
					}
				}
			}
			
			
			$command = $this->container->get('ems.environment.reindex');
			
			$arguments = array(
					'name'    => $name,
					'index'   => $indexName
			);
			
			$reindexInput = new ArrayInput($arguments);
			$returnCode = $command->run($reindexInput, $output);
			
			if($returnCode){
				$output->writeln('Reindexed with return code: '.$returnCode);				
			}
			
				
			$this->switchAlias($environment->getAlias(), $indexName, true, $output);
			$output->writeln('The alias <info>'.$environment->getName().'</info> is now pointing to '.$indexName);
		}
		else{
			$output->writeln("WARNING: Environment named ".$name." not found");
		}
    }
    


    /**
     * Update the alias of an environement to a new index
     *
     * @param string $alias
     * @param string $to
     */
    private function switchAlias($alias, $to, $newEnv=false, OutputInterface $output){
    	try{
    			
    		
    		$result = $this->client->indices()->getAlias(['name' => $alias]);
    		$index = array_keys ( $result ) [0];
    		$params ['body'] = [
    				'actions' => [
    						[
    								'remove' => [
    										'index' => $index,
    										'alias' => $alias
    								],
    								'add' => [
    										'index' => $to,
    										'alias' => $alias
    								]
    						]
    				]
    		];
    		$this->client->indices ()->updateAliases ( $params );
    	}
    	catch(Missing404Exception $e){
    		if(!$newEnv){
    			$output->writeln ( 'WARNING : Alias '.$alias.' not found' );
    		}
    		$this->client->indices()->putAlias([
    				'index' => $to,
    				'name' => $alias
    		]);
    	}
    
    }
}