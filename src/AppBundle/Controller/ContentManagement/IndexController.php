<?php

namespace AppBundle\Controller\ContentManagement;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Controller\AppController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Entity\ContentType;
use AppBundle;
use Doctrine\ORM\EntityManager;
use AppBundle\Repository\RevisionRepository;

class IndexController extends AppController
{
	/**
	 * @Route("/indexes/content-type/{contentTypeId}/{alias}", name="index.content-type")
	 */
	public function reindexContentTypeAction($contentTypeId, $alias)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
		/** @var Revision $revision */
// 		$revisions = $repository->findBy();
		
			
			return $this->render( 'default/coming-soon.html.twig');
		
		
	}
}