<?php
namespace AppBundle\Controller;

use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\EnvironmentRepository;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\Form\SearchFormType;
use AppBundle\Entity\Form\Search;

class ElasticsearchController extends Controller
{
	/**
	 * @Route("/status.{_format}", defaults={"_format": "html"}, name="elasticsearch.status"))
	 */
	public function statusAction($_format)
	{
		try {
			$client = $this->get('app.elasticsearch');
			$status = $client->cluster()->health();
			
			if('html' === $_format && 'green' !== $status['status']){
				if('red' === $status['status']){
					$this->addFlash(
						'error',
						'There is something wrong with the cluster! Actions are required now.'
					);
				}
				else {
					$this->addFlash(
						'warning',
						'There is something wrong with the cluster. Status: <strong>'.$status['status'].'</strong>.'
					);
				}
			}
			
			return $this->render( 'elasticsearch/status.'.$_format.'.twig', [
					'status' => $status
			] );			
		}
		catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e){
			return $this->render( 'elasticsearch/no-nodes-available.'.$_format.'.twig');
		}
	}
	

	/**
	 * @Route("/elasticsearch/index/delete/{name}", name="elasticsearch.index.delete"))
	 */
	public function deleteIndexAction($name, Request $request)
	{
		/** @var  Client $client */
		$client = $this->get('app.elasticsearch');
		try {
			$indexes = $client->indices()->get(['index' => $name]);
			$client->indices()->delete([
					'index' => $name
			]);
			$this->addFlash('notice', 'Elasticsearch index '.$name.' has been deleted');
		}
		catch (Missing404Exception $e){
			$this->addFlash('warning', 'Elasticsearch index not found');
		}
		return $this->redirectToRoute('environment.index');
	}
	
	/**
	 * @Route("/search/{query}", defaults={"query"=null}, name="elasticsearch.search"))
	 */
	public function searchAction($query, Request $request)
	{
		try {
			
			if(null != $request->query->get('page')){
				$page = $request->query->get('page');
			}
			else{
				$page = 1;
			}
			
			
			$search = new Search();


			
			$form = $this->createForm ( SearchFormType::class, $search, [
					'method' => 'GET'
			] );

			$form->handleRequest ( $request );
			
			$form->isValid();
			
			/** @var Search $search */
			$search = $form->getData();
			
			
			$body = [];
			/** @var SearchFilter $filter */
			foreach ($search->getFilters() as $filter){
					
				$esFilter = $filter->generateEsFilter();
					
				if($esFilter){
					$body["query"][$search->getBoolean()][] = $esFilter;
				}	
					
			}			
			
			/** @var EntityManager $em */
			$em = $this->getDoctrine()->getManager();
			
			/** @var ContentTypeRepository $contentTypeRepository */
			$contentTypeRepository = $em->getRepository ( 'AppBundle:ContentType' );
				
			$types = $contentTypeRepository->findAllAsAssociativeArray();
			
			/** @var EnvironmentRepository $environmentRepository */
			$environmentRepository = $em->getRepository ( 'AppBundle:Environment' );
			
			$environments = $environmentRepository->findAllAsAssociativeArray('alias');

			/** @var \Elasticsearch\Client $client */
			$client = $this->get('app.elasticsearch');
			
			$assocAliases = $client->indices()->getAliases();
			
			$mapAlias = [];
			$mapIndex = [];
			foreach ($assocAliases as $index => $aliasNames){
				foreach ($aliasNames['aliases'] as $alias => $options){
					if(isset($environments[$alias])){
						$mapAlias[$environments[$alias]['alias']] = $environments[$alias];
						$mapIndex[$index] = $environments[$alias];
						break;
					}
				}
			}
			
// 			dump($mapAlias);
			$params = [
					'version' => true, 
// 					'df'=> isset($field)?$field:'_all',
					'index' => $search->getAliasFacet() != null?$search->getAliasFacet():array_keys($environments),
					'type' => $search->getTypeFacet() != null?$search->getTypeFacet():array_keys($types),
					'size' => $this->container->getParameter('paging_size'), 
					'from' => ($page-1)*$this->container->getParameter('paging_size')
				
			];
		
			
			
			$body = array_merge($body, json_decode('{
			   "highlight": {
			      "fields": {
			         "_all": {}
			      }
			   },
			   "aggs": {
			      "types": {
			         "terms": {
			            "field": "_type"
			         }
			      },
			      "indexes": {
			         "terms": {
			            "field": "_index"
			         }
			      }
			   }
			}', true));
			

			
			$params['body'] = $body;
			
// 			dump($params);
			$results = $client->search($params);
	
		
			$lastPage = ceil($results['hits']['total']/$this->container->getParameter('paging_size'));
			
// 			dump($request);

// 			if($lastPage)
			
			return $this->render( 'elasticsearch/search.html.twig', [
					'results' => $results,
					'lastPage' => $lastPage,
					'paginationPath' => 'elasticsearch.search',
					'types' => $types,
					'alias' => $mapAlias,
					'indexes' => $mapIndex,
					'form' => $form->createView(),
					'page' => $page,
			] );
		}
		catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e){
			return $this->redirectToRoute('elasticsearch.status');
		}
	}
	
	
}