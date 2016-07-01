<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Controller\AppController;
use AppBundle;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\ContentType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\EnvironmentRepository;
use AppBundle\Entity\Environment;
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
		
		
		$this->get("ems.service.notification")->addNotification($templateId, reset($revision), $env);

		return $this->render( 'data/ajax-notification.json.twig', [
				'message' => true,
		] );
	}
}