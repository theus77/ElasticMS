<?php

namespace AppBundle\Service;


use AppBundle\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Elasticsearch\Client;
use Monolog\Logger;

class NotificationService {
	
	// index elasticSerach
	protected $index;
	/**@var Registry $doctrine */
	protected $doctrine;
	/**@var UserService $userService*/
	protected $userService;
	/**@var Logger $logger*/
	protected $logger;
	/**@var AuditService $auditService*/
	protected $auditService;
	
	public function __construct($index, Registry $doctrine, UserService $userService, Logger $logger, AuditService $auditService)
	{
		$this->index = $index;
		$this->doctrine = $doctrine;
		$this->userService = $userService;
		$this->logger = $logger;
		$this->auditService = $auditService;		
	} 
	
	
	/**
	 * Call addNotification when click on a request
	 * 
	 * @param unknown $template
	 * @param unknown $revision
	 */
	public function addNotification($templateId, $revision, $environment) {
		try{
			$notification =  new Notification();
			
			$notification->setStatus('pending');
			
			$em = $this->doctrine->getManager();
			
			/** @var RevisionRepository $repository */
			$repository = $em->getRepository('AppBundle:Template');
			/** @var Revision $revision */
			$template = $repository->findOneById($templateId);
			
			if(!$template) {
				throw new NotFoundHttpException('Unknown template');
			}

			$notification->setTemplateId($template);
			$sentTimestamp = new \DateTime();
			$notification->setSentTimestamp($sentTimestamp);
			
			$notification->setEnvironmentId($environment);
			
			$notification->setRevisionId($revision);
			$userName = $this->userService->getCurrentUser()->getUserName();
			$notification->setUsername($userName);
			
			$em->persist($notification);
			$em->flush();
		}
		catch(\Exception $e){
			$this->logger->err('An error occured: '.$e->getMessage());
		}
	}
	
	/**
	 * Call to display notifications in header menu
	 * 
	 * @return int
	 */
	public function menuNotification() {
		
		$em = $this->doctrine->getManager();
		/** @var NotificationRepository $repository */
		$repository = $em->getRepository('AppBundle:Notification');
		
		$count = $repository->countPendingByUserRoleAndCircle($this->userService->getCurrentUser());

		return $count;
	}
	
	/**
	 * Call to generate list of notifications
	 * 
	 * @return array Notification
	 */
	public function listNotifications() {
		$em = $this->doctrine->getManager();
		/** @var NotificationRepository $repository */
		$repository = $em->getRepository('AppBundle:Notification');
		
		$notifications = $repository->findByPendingAndUserRoleAndCircle($this->userService->getCurrentUser());
		dump($notifications);
		
		return $notifications;
	}
}