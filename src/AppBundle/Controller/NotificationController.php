<?php

namespace AppBundle\Controller;

use AppBundle\Controller\AppController;
use AppBundle;
use AppBundle\Entity\ContentType;
use AppBundle\Entity\Environment;
use AppBundle\Entity\Form\NotificationFilter;
use AppBundle\Entity\Form\TreatNotifications;
use AppBundle\Entity\Notification;
use AppBundle\Form\Form\NotificationFormType;
use AppBundle\Form\Form\TreatNotificationsType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\EnvironmentRepository;
use AppBundle\Service\NotificationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationController extends AppController
{
	
	/**
	 * @Route("/notification/add/{objectId}.json", name="notification.ajaxnotification"))
	 * @Method({"POST"})
	 */
	public function ajaxNotificationAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
	
		$templateId = $request->request->get('templateId');
		$environmentName = $request->request->get('environmentName');
		$ctId = $request->request->get('contentTypeId');
		$ouuid = $request->request->get('ouuid');
		
		/** @var EnvironmentRepository $repositoryEnv */
		$repositoryEnv = $em->getRepository('AppBundle:Environment');
		/** @var Environment $env */
		$env = $repositoryEnv->findOneByName($environmentName);
		
		if(!$env) {
			throw new NotFoundHttpException('Unknown environment');
		}
			
		/** @var ContentTypeRepository $repositoryCt */
		$repositoryCt = $em->getRepository('AppBundle:ContentType');
		/** @var ContentType $ct */
		$ct = $repositoryCt->findOneById($ctId);
		
		if(!$ct) {
			throw new NotFoundHttpException('Unknown content type');
		}
			
		
		/** @var RevisionRepository $repositoryRev */
		$repositoryRev = $em->getRepository('AppBundle:Revision');
		/** @var Revision $revision */
		$revision = $repositoryRev->findByOuuidAndContentTypeAndEnvironnement($ct, $ouuid, $env);
		if(!$revision) {
			throw new NotFoundHttpException('Unknown revision');
		}
		
		/**@var NotificationService $notificationService*/
		$notificationService = $this->get("ems.service.notification");
		$success = $notificationService->addNotification($templateId, reset($revision), $env);

		return $this->render( 'ajax/notification.json.twig', [
				'success' => $success,
		] );
	}
	
	
	/**
	 * @Route("/notification/treat", name="notification.treat"))
     * @Method({"POST"})
	 */
	public function treatNotificationsAction(Request $request)
	{
		$treatNotification = new TreatNotifications();
		$form = $this->createForm(TreatNotificationsType::class, $treatNotification, [
		]);
		$form->handleRequest ( $request );
		/**@var TreatNotifications $treatNotification*/
		$treatNotification = $form->getNormData();
		$treatNotification->setAccept($form->get('accept')->isClicked());
		$treatNotification->setReject($form->get('reject')->isClicked());


		/**dump in flash all info, should be replace by a call to a service's fucniton*/
		foreach( $treatNotification->getNotifications() as $notification => $true ){
			$this->addFlash('notice', 'Should treat notice #'.$notification);
		}

		$this->addFlash('notice', 'Should publish them in '.$treatNotification->getPublishTo());
		$this->addFlash('notice', 'Should unpublish them from '.$treatNotification->getUnpublishfrom());
		
		if($treatNotification->getAccept()){
			$this->addFlash('notice', 'Those notices are accepted');
		}
		else {
			$this->addFlash('notice', 'Those notices are rejected');
		}	
		/** end dump **/
		
		
		return $this->redirectToRoute('notifications.list');
	}
	
	
	/**
	 * @Route("/notification/menu", name="notification.menu"))
	 */
	public function menuNotificationAction()
	{
		// TODO use a servce to pass authorization_checker to repositoryNotification.
		$em = $this->getDoctrine()->getManager();
		$repositoryNotification = $em->getRepository('AppBundle:Notification');
		$repositoryNotification->setAuthorizationChecker($this->get('security.authorization_checker'));
		
		$vars['counter'] = $this->get('ems.service.notification')->menuNotification();
		
		return $this->render('notification/menu.html.twig', $vars);
	}
	
	/**
	 * @Route("/notifications/list", name="notifications.list")
	 */
	public function listNotificationsAction(Request $request)
	{
 		$filters = $request->query->get('notification_form');

 		//TODO: Why do we need to unset these fields ? 
//  		if (is_array($filters)) {
//  			unset($filters['filter']);
//  			unset($filters['_token']);
//  		}
 		
		$notificationFilter = new NotificationFilter();
		
 		$form = $this->createForm(NotificationFormType::class, $notificationFilter, [
 				'method' => 'GET'
 		]);
 		$form->handleRequest ( $request );
 		
 		if($form->isSubmitted()){
 			$notificationFilter = $form->getData();
 		}
 		

 		
		//TODO: use a servce to pass authorization_checker to repositoryNotification.
		$em = $this->getDoctrine()->getManager();
		$repositoryNotification = $em->getRepository('AppBundle:Notification');
		$repositoryNotification->setAuthorizationChecker($this->get('security.authorization_checker'));
	
 		$count = $this->get('ems.service.notification')->menuNotification($filters);
		
		// for pagination
		$paging_size = $this->getParameter('paging_size');
		$lastPage = ceil($count/$paging_size);;
		if(null != $request->query->get('page')){
			$page = $request->query->get('page');
		}
		else{
			$page = 1;
		}
		
		$notifications = $this->get('ems.service.notification')->listNotifications(($page-1)*$paging_size, $paging_size, $filters);

 		$treatNotification = new TreatNotifications();
//  		$forForm = [];
//  		foreach ($notifications as $notification){
//  			$forForm[$notification->getId()] = false;
//  		}
//  		$treatNotification->setNotifications($forForm);
 		
 		/**@var \Symfony\Component\Routing\RouterInterface $router*/
 		$router = $this->get('router');
 		$treatform = $this->createForm(TreatNotificationsType::class, $treatNotification, [
 				'action' => $router->generate('notification.treat'),
 				'notifications' => $notifications,
 		]);
		
		return $this->render('notification/list.html.twig', array(
				'counter' => $count,
				'notifications' => $notifications,
				'lastPage' => $lastPage,
				'paginationPath' => 'notifications.list',
				'page' => $page,
				'form' => $form->createView(),
				'treatform' => $treatform->createView(),
				'currentFilters' => $request->query
		));
	}
}