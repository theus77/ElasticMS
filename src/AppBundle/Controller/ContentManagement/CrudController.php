<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle;
use AppBundle\Controller\AppController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CrudController extends AppController
{
	private function dataService() {
		return $this->get('ems.service.data');
	}
	
	protected static function prepareResponse() {
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	/**
	 * @Route("/api/create")
     * @Method({"POST"})
	 */
	public function createAction(Request $request) {
		return $this->render( 'ajax/notification.json.twig', [
				'success' => true,
		] );
	}
	
	/**
	 * @Route("/api/test", name="api.test")
     * @Method({"GET"})
	 */
	public function testAction(Request $request) {
		return $this->render( 'ajax/notification.json.twig', [
				'success' => true,
		], $this->prepareResponse() );
	}
}