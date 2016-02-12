<?php

namespace AppBundle\Controller\ContentManagement;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Controller\AppController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Entity\ContentType;

class IndexController extends AppController
{
	/**
	 * @Route("/data/{contentType}/index/{page}.{_format}", defaults={"page": 1, "_format": "html"}, name="data.index"))
	 */
	public function indexAction($contentType, $_format, $page)
	{
		
		$repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:ContentType');
		$contentTypes = $repository->findBy([
				'deleted' => false,
				'rootContentType' => true,
				'name' => $contentType
		]);
		
		
		if($contentTypes && count($contentTypes) > 0){
			$client = $this->getElasticsearch();
			$results = $client->search([
					'index' => $contentTypes[0]->getAlias(),
					'version' => true, 
					'size' => $this->container->getParameter('paging_size'), 
					'from' => ($page-1)*$this->container->getParameter('paging_size'),
						'type' => $contentType
			]);
			
			if( null != $contentTypes[0]->getIndexTwig() ) {
				$twig = $this->getTwig();
				$template = $twig->createTemplate($contentTypes[0]->getIndexTwig());
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
							'contentType' => $contentType,
							'page' =>  $page,
							'_format' => $_format
					],
					'contentType' =>  $contentTypes[0]
			] );
			
		}
		
		throw new NotFoundHttpException();
		
	}
}