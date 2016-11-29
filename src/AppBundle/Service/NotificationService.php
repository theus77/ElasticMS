<?php

namespace AppBundle\Service;


use AppBundle\Entity\Form\TreatNotifications;
use AppBundle\Entity\Notification;
use AppBundle\Entity\Template;
use AppBundle\Entity\User;
use AppBundle\Repository\NotificationRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use FOS\UserBundle\Mailer\Mailer;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Session\Session;

class NotificationService {
	
	// index elasticSerach
	private $index;
	/**@var Registry $doctrine */
	private $doctrine;
	/**@var UserService $userService*/
	private $userService;
	/**@var Logger $logger*/
	private $logger;
	/**@var AuditService $auditService*/
	private $auditService;
	/**@var Session $session*/
	private $session;
	/**@var Container $container*/
	private $container;
	/**@var DataService $dataService*/
	private $dataService;
	private $sender;
	/**@var \Twig_Environment $twig*/
	private $twig;
	
	//** non-service members **
	/**@var OutputInterface $output*/
	private $output;
	private $dryRun;

	
	public function __construct($index, Registry $doctrine, UserService $userService, Logger $logger, AuditService $auditService, Session $session, Container $container, DataService $dataService, $sender, \Twig_Environment $twig)
	{
		$this->index = $index;
		$this->doctrine = $doctrine;
		$this->userService = $userService;
		$this->dataService = $dataService;
		$this->logger = $logger;
		$this->auditService = $auditService;	
		$this->session = $session;
		$this->container = $container;
		$this->twig = $twig;
		$this->output = NULL;
		$this->dryRun = false;
		$this->sender = $sender;
	} 
	

	public function setOutput($output){
		$this->output = $output;
	}
	public function setDryRun($dryRun){
		$this->dryRun = $dryRun;
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
			
			$em = $this->doctrine->getManager();
			
			/** @var RevisionRepository $repository */
			$repository = $em->getRepository('AppBundle:Template');
			/** @var Template $template */
			$template = $repository->findOneById($templateId);
			
			if(!$template) {
				throw new NotFoundHttpException('Unknown template');
			}

			

			$notification =  new Notification();
			
			$notification->setStatus('pending');
			
			$em = $this->doctrine->getManager();
			/** @var NotificationRepository $repository */
			$repository = $em->getRepository('AppBundle:Notification');
			
			
			
			$alreadyPending = $repository->findBy([
					'template' => $template,
					'revision' => $revision,
					'environment' => $environment,
					'status' => 'pending',
			]);
			
			if(! empty($alreadyPending)){
				/**@var Notification $alreadyPending*/
				$alreadyPending = $alreadyPending[0];
				$this->session->getFlashBag()->add('warning', 'Another notification '.$template.' is already pending for '.$revision.' in '.$environment.' by '. $alreadyPending->getUsername());
				return;
			}
			
			$notification->setTemplate($template);
			$sentTimestamp = new \DateTime();
			$notification->setSentTimestamp($sentTimestamp);
			
			$notification->setEnvironment($environment);
			
			$notification->setRevision($revision);
			$userName = $this->userService->getCurrentUser()->getUserName();
			$notification->setUsername($userName);
			$em->persist($notification);
			$em->flush();

			try{
				$this->sendEmail($notification);
			}
			catch (\Exception $e) {
				
			}
			
			$this->session->getFlashBag()->add('notice', '<i class="'.$template->getIcon().'"></i> '.$template->getName().' for '.$revision.' in '.$environment->getName());
			$out = true;
		}
		catch(\Exception $e){
			$this->session->getFlashBag()->add('error', '<i class="fa fa-ban"></i> An error occured while sending a notification');
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
	public function listInboxNotifications($from, $limit, $filters = null) {
		
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
		

		/**@var Notification $notification*/
		foreach ($notifications as $notification) {
			$result = $repository->countNotificationByUuidAndContentType($notification->getRevision()->getOuuid(), $notification->getRevision()->getContentType());
			
			$notification->setCounter($result);
		}
			
		return $notifications;
	}
	
	/**
	 * Call to generate list of notifications
	 * 
	 * @return array Notification
	 */
	public function listArchivesNotifications($from, $limit, $filters = null) {
		
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
		

		/**@var Notification $notification*/
		foreach ($notifications as $notification) {
			$result = $repository->countNotificationByUuidAndContentType($notification->getRevision()->getOuuid(), $notification->getRevision()->getContentType());
			
			$notification->setCounter($result);
		}
			
		return $notifications;
	}
	
	/**
	 * Call to generate list of notifications
	 * 
	 * @return array Notification
	 */
	public function listSentNotifications($from, $limit, $filters = null) {
		
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
		$notifications = $repository->findByPendingAndRoleAndCircleForUserSent($this->userService->getCurrentUser(), $from, $limit, $contentTypes, $environments, $templates);
		

// 		/**@var Notification $notification*/
// 		foreach ($notifications as $notification) {
// 			$result = $repository->countNotificationByUuidAndContentType($notification->getRevision()->getOuuid(), $notification->getRevision()->getContentType());
			
// 			$notification->setCounter($result);
// 		}
			
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

		try{
			$this->sendEmail($notification);
		}
		catch (\Exception $e) {
		
		}
		
		$this->session->getFlashBag()->add('notice', '<i class="'.$notification->getTemplate()->getIcon().'"></i> '.$notification->getTemplate()->getName().' for '.$notification->getRevision().' has been '.$notification->getStatus());
	}

	public function accept(Notification $notification, TreatNotifications $treatNotifications) {
		$this->response($notification, $treatNotifications, 'accepted');
	}

	public function reject(Notification $notification, TreatNotifications $treatNotifications) {
		$this->response($notification, $treatNotifications, 'rejected');	
	}

	private function buildBodyPart(User $user, Template $template, $as) {
		$em = $this->doctrine->getManager();
		/** @var NotificationRepository $repository */
		$this->repository = $em->getRepository('AppBundle:Notification');
		
		$notifications = $this->repository->findBy([
			'status' => 'pending',
		]);
		
		foreach ($notifications as $notification){
			if($this->output) {
				$this->output->writeln('found'.$notification);
			}
		}
	}
	
	public static function usersToEmailAddresses($users){
		$out = [];
		/**@var User $user*/
		foreach ($users as $user){
			$out[$user->getEmail()] = $user->getDisplayName();
		}
		return $out;
	}
	
	public function sendEmail(Notification $notification) {
		
		$fromCircles = $this->dataService->getDataCircles($notification->getRevision());
		
		$toCircles = array_unique(array_merge($fromCircles, $notification->getTemplate()->getCirclesTo()));

		$fromUser = $this->usersToEmailAddresses([$this->userService->getUser($notification->getUsername())]);
		$toUsers = $this->usersToEmailAddresses($this->userService->getUsersForRoleAndCircles($notification->getTemplate()->getRoleTo(), $toCircles));
		$ccUsers = $this->usersToEmailAddresses($this->userService->getUsersForRoleAndCircles($notification->getTemplate()->getRoleCc(), $toCircles));
		
		$message = \Swift_Message::newInstance();
		
		$params = [
				'notification' => $notification,
				'source' => $notification->getRevision()->getRawData(),
				'object' => $notification->getRevision()->buildObject(),
				'status' => $notification->getStatus(),
				'environment' => $notification->getEnvironment(),
		];
		
		if($notification->getStatus() == 'pending') {
			//it's a notification
			try {
				$body = $this->twig->createTemplate($notification->getTemplate()->getBody())->render($params);
			}
			catch (\Exception $e) {
				$body = "Error in body template: ".$e->getMessage();
			}
			
			$message->setSubject($notification->getTemplate().' for '.$notification->getRevision())
				->setFrom($this->sender['address'], $this->sender['sender_name'])
				->setTo($toUsers)
				->setCc(array_unique(array_merge($ccUsers, $fromUser)))
				->setBody($body, empty($notification->getTemplate()->getEmailContentType())?'text/html':$notification->getTemplate()->getEmailContentType());
			$notification->setEmailed(new \DateTime());
		}
		else{
			//it's a notification
			try {
				$body = $this->twig->createTemplate($notification->getTemplate()->getResponseTemplate())->render($params);
			}
			catch (\Exception $e) {
				$body = "Error in response template: ".$e->getMessage();
			}
			
			//it's a reminder
			$message->setSubject($notification->getTemplate().' for '.$notification->getRevision().' has been '.$notification->getStatus())
				->setFrom($this->sender['address'], $this->sender['sender_name'])
				->setTo($fromUser)
				->setCc(array_unique(array_merge($ccUsers, $toUsers)))
				->setBody($body, 'text/html');
			$notification->setResponseEmailed(new \DateTime());
		}
		
		if(!$this->dryRun) {
			$em = $this->doctrine->getManager();
			try{
				/**@Swift_Mailer $mailer*/
				$mailer = $this->container->get('mailer');
				$mailer->send($message);				
				$em->persist($notification);
				$em->flush();
			}
			catch (\Swift_TransportException $e){
			}
		}
	}
}