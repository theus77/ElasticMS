<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle;
use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle\Service\DataService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CrudController extends AppController
{
	/**
	 * 
	 * @return DataService
	 */
	private function dataService() {
		return $this->get('ems.service.data');
	}
	
	
	/**
	 * @Route("/api/{name}/create/{ouuid}", defaults={"ouuid": null, "_format": "json"})
	 * @Route("/api/{name}/draft/{ouuid}", defaults={"ouuid": null, "_format": "json"})
     * @ParamConverter("contentType", options={"mapping": {"name": "name", "deleted": 0, "active": 1}})
     * @Method({"POST"})
	 */
	public function createAction($ouuid, ContentType $contentType, Request $request) {
		
		if(!$contentType->getEnvironment()->getManaged()){
			throw new BadRequestHttpException('You can not create content for a managed content type');	
		}
		
		$rawdata = json_decode($request->getContent(), true);
		if (empty($rawdata)){
			throw new BadRequestHttpException('Not a valid JSON message');	
		}
		
		$newRevision = $this->dataService()->createData($ouuid, $rawdata, $contentType);
		
		return $this->render( 'ajax/notification.json.twig', [
				'success' => true,
				'revision_id' => $newRevision->getId(),
		]);
	}
	
	
	/**
	 * @Route("/api/{name}/finalize/{id}", defaults={"_format": "json"})
     * @ParamConverter("contentType", options={"mapping": {"name": "name", "deleted": 0, "active": 1}})
     * @Method({"GET"})
	 */
	public function finalizeAction($id, ContentType $contentType, Request $request) {
		
		if(!$contentType->getEnvironment()->getManaged()){
			throw new BadRequestHttpException('You can not create content for a managed content type');	
		}
		
		$revision = $this->dataService()->getRevisionById($id, $contentType);
		
		$newRevision = $this->dataService()->finalizeDraft($revision);
		
		$finalize = !$newRevision->getDraft();
		
		return $this->render( 'ajax/notification.json.twig', [
				'success' => $finalize,
		]);
	}
	
	/**
	 * @Route("/api/{name}/discard/{id}", defaults={"_format": "json"})
	 * @ParamConverter("contentType", options={"mapping": {"name": "name", "deleted": 0, "active": 1}})
	 * @Method({"GET"})
	 */
	public function discardAction($id, ContentType $contentType, Request $request) {
	
		if(!$contentType->getEnvironment()->getManaged()){
			throw new BadRequestHttpException('You can not create content for a managed content type');
		}
	
		$revision = $this->dataService()->getRevisionById($id, $contentType);
	
		$this->dataService()->discardDraft($revision);
	
		return $this->render( 'ajax/notification.json.twig', [
				'success' => true,
		]);
	}
	
	/**
	 * @Route("/api/test", defaults={"_format": "json"}, name="api.test")
     * @Method({"GET"})
	 */
	public function testAction(Request $request) {
		return $this->render( 'ajax/notification.json.twig', [
				'success' => true,
		]);
	}
}