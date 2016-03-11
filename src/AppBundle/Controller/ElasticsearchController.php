<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\EnvironmentRepository;
use AppBundle\Form\Form\SearchType;

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
			$q = $request->query->get('q');
			$typeFacet = $request->query->get('type');
			$indexFacet = $request->query->get('index');
			$field = $request->query->get('field');
			$page = $request->query->get('page');
			if(!isset($page)){
				$page = 1;
			}
			
			if(isset($q)){
				return $this->redirectToRoute('elasticsearch.search', array(
						'query' => $q,
						'field' => $field,
						'page' => $page,
						'type' => $typeFacet,
						'index' => $indexFacet
				));
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
			foreach ($assocAliases as $index => $aliasNames){
				foreach ($aliasNames['aliases'] as $alias => $options){
					if(isset($environments[$alias])){
						$mapAlias[$index] = $environments[$alias];
						break;
					}
				}
			}
			
			
			if(! isset($query)){
				$es_query = '{
    			    "query": {
				    	"match_all" : { }
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
				   }}';
			}
			else {
				
				$es_query = 
				'{
				   "query": {
				      "query_string": {
				         "default_field": '.((isset($field) && strlen($field)) > 0?json_encode($field):'"_all"').',
				         "query": '.json_encode($query).',
				         "default_operator": "AND"
				      }
				   },
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
				}';	
			}
			
// 			echo $es_query; exit;	
			
			$results = $client->search([
					'body' => $es_query, 
					'version' => true, 
// 					'df'=> isset($field)?$field:'_all',
					'index' => isset($indexFacet)?$indexFacet:array_keys($environments),
					'type' => isset($typeFacet)?$typeFacet:array_keys($types),
					'size' => $this->container->getParameter('paging_size'), 
					'from' => ($page-1)*$this->container->getParameter('paging_size')]);
	
		

			$lastPage = ceil($results['hits']['total']/$this->container->getParameter('paging_size'));
			
			return $this->render( 'elasticsearch/search.html.twig', [
					'results' => $results,
					'lastPage' => $lastPage,
					'paginationPath' => 'elasticsearch.search',
					'currentFilters' => [
							'query' => $query,
							'page' =>  $page,
							'type' => $typeFacet,
							'index' => $indexFacet,
							'field' => $field,
					],
					'types' => $types,
					'alias' => $mapAlias,
			] );
		}
		catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e){
			return $this->redirectToRoute('elasticsearch.status');
		}
	}
	
	
}