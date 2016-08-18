<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle;
use AppBundle\Entity\Environment;
use AppBundle\Entity\Revision;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PublishController extends AppController
{
	/**
	 * @Route("/publish/to/{revisionId}/{envId}", name="revision.publish_to"))
	 */
	public function publishToAction(Revision $revisionId, Environment $envId, Request $request)
	{
		$this->get("ems.service.publish")->publish($revisionId, $envId);
		
		return $this->redirectToRoute('data.revisions', [
				'ouuid' => $revisionId->getOuuid(),
				'type'=> $revisionId->getContentType()->getName(),
				'revisionId' => $revisionId->getId(),
		]);
	}
	
	/**
	 * @Route("/revision/unpublish/{revisionId}/{envId}", name="revision.unpublish"))
	 */
	public function unpublishAction(Revision $revisionId, Environment $envId, Request $request)
	{
		$this->get("ems.service.publish")->unpublish($revisionId, $envId);
		
		return $this->redirectToRoute('data.revisions', [
				'ouuid' => $revisionId->getOuuid(),
				'type'=> $revisionId->getContentType()->getName(),
				'revisionId' => $revisionId->getId(),
		]);		
	}
}