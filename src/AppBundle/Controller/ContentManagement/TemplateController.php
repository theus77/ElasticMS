<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle;
use AppBundle\Repository\ContentTypeRepository;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TemplateController extends AppController
{
	/**
	 * @Route("/template/{type}", name="template.index")
	 */
	public function indexAction($type, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
		
		$contentTypes = $contentTypeRepository->findBy([
			'deleted' => false,
			'name' => $type,
		]);
			
		if(!$contentTypes || count($contentTypes) != 1) {
			throw new NotFoundHttpException('Content type not found');
		}
		
		
		return $this->render( 'template/index.html.twig', [
				'contentType' => $contentTypes[0]
		]);
		
		
	}
	
	/**
	 * @Route("/template/add/{type}", name="template.add")
	 */
	public function addAction($type, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
		
		$contentTypes = $contentTypeRepository->findBy([
			'deleted' => false,
			'name' => $type,
		]);
			
		if(!$contentTypes || count($contentTypes) != 1) {
			throw new NotFoundHttpException('Content type not found');
		}
		
		
		return $this->render( 'template/index.html.twig', [
				'contentType' => $contentTypes[0]
		]);
		
		
	}
}