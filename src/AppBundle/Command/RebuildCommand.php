<?php

// src/AppBundle/Command/GreetCommand.php
namespace AppBundle\Command;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle\Entity\Environment;
use AppBundle\Repository\JobRepository;
use AppBundle\Service\ContentTypeService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Monolog\Logger;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Session\Session;

class RebuildCommand extends EmsCommand
{
	private $mapping;
	private $doctrine;
	private $container;
	
	/**@var ContentTypeService*/
	private $contentTypeService;
	private $instanceId;
	
	public function __construct(Registry $doctrine, Logger $logger, Client $client, $mapping, Container $container, Session $session, $instanceId)
	{
		$this->doctrine = $doctrine;
		$this->logger = $logger;
		$this->client = $client;
		$this->mapping = $mapping;
		$this->container = $container;
		$this->contentTypeService = $container->get('ems.service.contenttype');
		$this->session = $session;
		$this->instanceId = $instanceId;
		parent::__construct($logger, $client, $session);
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

    	$this->waitForGreen($output);
    	
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
			if($environment->getAlias() != $this->instanceId.$environment->getName()) {
				$environment->setAlias($this->instanceId.$environment->getName());
				$em->persist($environment);
				$em->flush();				
				$output->writeln("Alias has been aligned to ".$environment->getAlias());
			}
			
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
			$this->waitForGreen($output);
			
			// create a new progress bar
			$progress = new ProgressBar($output, count($contentTypes));
			// start and displays the progress bar
			$progress->start();
			$progressMessage = " creating content type's mappings in ".$indexName;
			
			/** @var ContentType $contentType */
			foreach ($contentTypes as $contentType){
				if($contentType->getEnvironment()->getManaged() && !$contentType->getDeleted()){
					$this->contentTypeService->updateMapping($contentType, $indexName);
				}

				$progress->advance();
				$output->write($progressMessage);
			}
			$progress->finish();
			$output->writeln($progressMessage);
			
			$this->flushFlash($output);			
			
			$command = $this->getReindexCommand();
			
			$arguments = array(
					'name'    => $name,
					'index'   => $indexName
			);
			
			$reindexInput = new ArrayInput($arguments);
			$returnCode = $command->run($reindexInput, $output);
			
			if($returnCode){
				$output->writeln('Reindexed with return code: '.$returnCode);				
			}
			
			$this->waitForGreen($output);
			$this->switchAlias($environment->getAlias(), $indexName, true, $output);
			$output->writeln('The alias <info>'.$environment->getName().'</info> is now pointing to '.$indexName);
		}
		else{
			$output->writeln("WARNING: Environment named ".$name." not found");
		}
		$this->flushFlash($output);
    }
    
    
    /*
     * @return ReindexCommand
     */
    protected function getReindexCommand() {
    	return $this->container->get('ems.environment.reindex');
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
    		$params ['body']['actions'] = [];
    		
    		foreach ($result as $id => $item){
    			$params ['body']['actions'][] = [
    				'remove' => [
	    				"index" => $id,
	    				"alias" => $alias,
    				]
    			];
    		}
    		
    		$params ['body']['actions'][] = [
    			'add' => [
	    			'index' => $to,
	    			'alias' => $alias,
    			]
    		];
    		
    		$this->client->indices()->updateAliases ( $params );
    	}
    	catch(\Exception $e){ //TODO why does Elasticsearch\Common\Exceptions\Missing404Exception is not catched?
    		
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