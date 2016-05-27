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
			/** @var \AppBundle\Entity\Revision $revision */
			foreach ($environment->getRevisions() as $revision) {
				if(!$revision->getDeleted() && !$revision->getContentType()->getDeleted()){
					$objectArray = $this->mapping->dataFieldToArray ($revision->getDataField());
					$status = $this->client->index([
							'index' => $index,
							'id' => $revision->getOuuid(),
							'type' => $revision->getContentType()->getName(),
							'body' => $objectArray
					]);		
					++$count;				
				}
			}

			$output->writeln($count.' objects have been reindexed in '.$index);
			
			while($this->client->count(['index' => $index])['count'] < $count){
				$output->writeln('<comment>Elasticsearch is indexing...</comment>');
				sleep(1);				
			}
		}
		else{
			$output->writeln("WARNING: Environment named ".$name." not found");
		}
    }
    
}