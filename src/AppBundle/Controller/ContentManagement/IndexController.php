<?php

namespace AppBundle\Controller\ContentManagement;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Controller\AppController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Entity\ContentType;
use AppBundle;

class IndexController extends AppController
{
	/**
	 * @Route("/data/index/{contentTypeId}/{page}.{_format}", defaults={"page": 1, "_format": "html"}, name="data.index"))
	 */
	public function indexAction($contentTypeId, $_format, $page)
	{
		$repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:ContentType');
		/** @var ContentType $contentType */
		$contentType = $repository->find($contentTypeId);
		
		
		if($contentType){
			$client = $this->getElasticsearch();
			$results = $client->search([
					'index' => $contentType->getAlias(),
					'version' => true, 
					'size' => $this->container->getParameter('paging_size'), 
					'from' => ($page-1)*$this->container->getParameter('paging_size'),
					'type' => $contentType->getName(),
			]);
			
			if( null != $contentType->getIndexTwig() ) {
				$twig = $this->getTwig();
				$template = $twig->createTemplate($contentType->getIndexTwig());
				foreach ($results['hits']['hits'] as &$hit){	
					try {
						
						$hit['_ems_twig_rendering'] = $template->render([
								'source' => $hit['_source'],
								'object' => $hit,
						]);				
					}
					catch (\Twig_Error $e){
						$hit['_ems_twig_rendering'] = "Error in the template: ".$e->getMessage();
					}
				}
			}
			
			return $this->render( 'data/index.'.$_format.'.twig', [
					'results' => $results,
					'lastPage' => ceil($results['hits']['total']/$this->container->getParameter('paging_size')),
					'paginationPath' => 'data.index',
					'currentFilters' => [
							'contentTypeId' => $contentTypeId,
							'page' =>  $page,
							'_format' => $_format
					],
					'contentType' =>  $contentType
			] );
			
		}
		
		throw new NotFoundHttpException();
		
	}
}