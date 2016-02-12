<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DataController extends AppController
{
	/**
	 * @Route("/data/add/{contentTypeId}", name="data.add"))
	 */
	public function addAction($contentTypeId)
	{
		/** @var ContentType $repository */
		$repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:ContentType');
		/** @var ContentType $contentType */
		$contentType = $repository->find($contentTypeId);
		
		return $this->render( 'data/add.html.twig', [
				'contentType' =>  $contentType
		] );
		
	}
	
}