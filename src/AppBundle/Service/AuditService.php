<?php

namespace AppBundle\Service;


use AppBundle\Entity\Audit;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Elasticsearch\Client;

class AuditService {
	
	// index elasticSerach
	protected $index;
	/**@var Registry $doctrine */
	protected $doctrine;
	/**@var Client $client*/
	protected $client;
	/**@var UserService $userService*/
	protected $userService;
	
	public function __construct($index, Registry $doctrine, Client $client, UserService $userService)
	{
		$this->index = $index;
		$this->doctrine = $doctrine;
		$this->client = $client;
		$this->userService = $userService;
	} 
	
	public function auditLog($action, $rawData, $environment = null) { 
		
		$date = new \DateTime();
		$userName = $this->userService->getCurrentUser()->getUserName();

		// if index is define (insert in index)
		if ($this->index != null) {
			$this->auditLogToIndex($action, $rawData, $environment);
		} else {
			// insert in DB
			$this->auditLogToDB($action, $rawData, $environment);
		}
	}
	
	public function auditLogToIndex($action, $rawData, $environment = null){
		try{
			$date = new \DateTime();
			$userName = $this->userService->getCurrentUser()->getUserName();
			$objectArray = ["action" => $action,
					"date" => $date,
					"raw_data" => serialize($rawData),
					"user" => $userName,
					"environment" => $environment
			];
			
			$status = $this->client->create([
					'index' => $this->index . '_' . date_format($date, 'Ymd'),
					'type' => 'Audit',
					'body' => $objectArray
			]);
		}
		catch(NotLockedException $e){
			$output->writeln("<error>'.$e.'</error>");
		}
	}
	
	public function auditLogToDB($action, $rawData, $environment = null){
		try{
			$audit = new Audit();
			$audit->setAction($action);
			$audit->setRawData(serialize($rawData));
			$audit->setEnvironment($environment);
			$date = new \DateTime();
			$audit->setDate($date);
			$userName = $this->userService->getCurrentUser()->getUserName();
			$audit->setUsername($userName);
			
			$em = $this->doctrine->getManager();
			$em->persist($audit);
			$em->flush();
		}
		catch(NotLockedException $e){
			$output->writeln("<error>'.$e.'</error>");
		}
	}
}