<?php

namespace AppBundle\Service;


use AppBundle\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Monolog\Logger;
use AppBundle\Entity\Template;
use Symfony\Component\HttpFoundation\Session\Session;
use AppBundle\Entity\Form\TreatNotifications;

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
	/**@var Session $session*/
	protected $session;
	
	public function __construct($index, Registry $doctrine, UserService $userService, Logger $logger, AuditService $auditService, Session $session)
	{
		$this->index = $index;
		$this->doctrine = $doctrine;
		$this->userService = $userService;
		$this->logger = $logger;
		$this->auditService = $auditService;	
		$this->session = $session;
	} 
	
	
	/**
	 * Call addNotification when click on a request
	 * 
	 * @param unknown $template
	 * @param unknown $revision
	 */
	public function addNotification($templateId, $revision, $environment) {
		$out = false;
		try{
			$notification =  new Notification();
			
			$notification->setStatus('pending');
			
			$em = $this->doctrine->getManager();
			
			/** @var RevisionRepository $repository */
			$repository = $em->getRepository('AppBundle:Template');
			/** @var Template $template */
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
			$this->session->getFlashBag()->add('notice', '<i class="'.$template->getIcon().'"></i> '.$template->getName().' for '.$revision.' in '.$environment->getName());
			$out = true;
		}
		catch(\Exception $e){
			$this->session->getFlashBag()->add('error', '<i class="fa fa-ban"></i> An error occured while sending a notificationi');
			$this->logger->err('An error occured: '.$e->getMessage());
		}
		return $out;
	}
	
	/**
	 * Call to display notifications in header menu
	 * 
	 * @return int
	 */
	public function menuNotification($filters = null) {
		
		$contentTypes = null;
		$environments = null;
		$templates = null;
		
		if($filters != null) {
			if (isset($filters['contentType'])) {
				$contentTypes = $filters['contentType'];
			} else if(isset($filters['environment'])) {
				$environments = $filters['environment'];
			} else if(isset($filters['template'])) {
				$templates = $filters['template'];
			}
		}
		
		$em = $this->doctrine->getManager();
		/** @var NotificationRepository $repository */
		$repository = $em->getRepository('AppBundle:Notification');
		
		$count = $repository->countPendingByUserRoleAndCircle($this->userService->getCurrentUser(), $contentTypes, $environments, $templates);

		return $count;
	}
	
	/**
	 * Call to generate list of notifications
	 * 
	 * @return array Notification
	 */
	public function listNotifications($from, $limit, $filters = null) {
		
		$contentTypes = null;
		$environments = null;
		$templates = null;
		
		if($filters != null) {
			if (isset($filters['contentType'])) {
				$contentTypes = $filters['contentType'];
			} else if(isset($filters['environment'])) {
				$environments = $filters['environment'];
			} else if(isset($filters['template'])) {
				$templates = $filters['template'];
			}
		} 
		
		$em = $this->doctrine->getManager();
		/** @var NotificationRepository $repository */
		$repository = $em->getRepository('AppBundle:Notification');
		$notifications = $repository->findByPendingAndUserRoleAndCircle($this->userService->getCurrentUser(), $from, $limit, $contentTypes, $environments, $templates);
			
		return $notifications;
	}
	
	private function response(Notification $notification, TreatNotifications $treatNotifications, $status) {
		$notification->setResponseText($treatNotifications->getResponse());
		$notification->setResponseTimestamp(new \DateTime());
		$notification->setResponseBy($this->userService->getCurrentUser()->getUsername());
		$notification->setStatus($status);
		$em = $this->doctrine->getManager();
		$em->persist($notification);
		$em->flush();
		
		$this->session->getFlashBag()->add('notice', '<i class="'.$notification->getTemplateId()->getIcon().'"></i> '.$notification->getTemplateId()->getName().' for '.$notification->getRevisionId().' has been '.$notification->getStatus());
	}

	public function accept(Notification $notification, TreatNotifications $treatNotifications) {
		$this->response($notification, $treatNotifications, 'accepted');
	}

	public function reject(Notification $notification, TreatNotifications $treatNotifications) {
		$this->response($notification, $treatNotifications, 'rejected');	
	}
}