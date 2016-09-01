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
		
		$this->dataService()->createData($ouuid, $rawdata, $contentType);
		
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