<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Repository\RevisionRepository;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Revision;
use AppBundle\Entity\Environment;

class PublishController extends AppController
{
	/**
	 * @Route("/publish/to/{revisionId}/{envId}", name="revision.publish_to"))
	 */
	public function publishToAction($revisionId, $envId, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var RevisionRepository $revisionRepo */
		$revisionRepo = $em->getRepository('AppBundle:Revision');
		
		/** @var Revision $revision */
		$revision = $revisionRepo->find($revisionId);
		
		if(!$revision) {
			throw $this->createNotFoundException('Revision not found');
		}
		

		/** @var RevisionRepository $revisionRepo */
		$environmentRepo = $em->getRepository('AppBundle:Environment');
		
		/** @var Environment $environment */
		$environment = $environmentRepo->find($envId);
		
		if(!$environment) {
			throw $this->createNotFoundException('Environment not found');
		}
		
		$result = $revisionRepo->findByOuuidContentTypeAndEnvironnement($revision, $environment);	
		
		/** @var Revision $item */
		foreach ($result as $item){
			$item->removeEnvironment($environment);
			$em->persist($item);
		}
		
		$revision->addEnvironment($environment);

		/** @var Client $client */
		$client = $this->get('app.elasticsearch');
		
		$objectArray = $revision->getDataField()->getObjectArray();
		$status = $client->index([
				'id' => $revision->getOuuid(),
				'index' => $environment->getName(),
				'type' => $revision->getContentType()->getName(),
				'body' => $objectArray
		]);
		
		$em->persist($revision);
		$em->flush();
		
		return $this->redirectToRoute('data.view', [
				'ouuid' => $revision->getOuuid()
		]);
		
	}
}