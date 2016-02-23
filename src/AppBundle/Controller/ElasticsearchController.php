<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
	 * @Route("/search/{query}/{page}", defaults={"query"=null, "page"=1}, name="elasticsearch.search"))
	 */
	public function searchAction($query, Request $request, $page)
	{
		
		$q = $request->query->get('q');
		
		if(isset($q)){
			return $this->redirectToRoute('elasticsearch.search', array('query' => $q));
		}
		
		
		if(! isset($query)){
			$es_query = '{
			    "query": {
			    	"match_all" : { }
			    },
			    "highlight" : {
			        "fields" : {
			            "_all" : {}
			        }
			    } }';
		}
		else {
			$es_query = '
			{
			   "query": {
			      "match": {
			         "_all": {
			            "query": '.json_encode($query).',
			            "operator": "and"
			         }
			      }
			   },
			    "highlight" : {
			        "fields" : {
			            "_all" : {}
			        }
			    }
			}';	
		}
		
		$client = $this->get('app.elasticsearch');
		$results = $client->search(['body' => $es_query, 'version' => true, 'size' => $this->container->getParameter('paging_size'), 'from' => ($page-1)*$this->container->getParameter('paging_size')]);

	
		return $this->render( 'elasticsearch/search.html.twig', [
				'results' => $results,
				'lastPage' => ceil($results['hits']['total']/$this->container->getParameter('paging_size')),
				'paginationPath' => 'elasticsearch.search',
				'currentFilters' => [
						'query' => $query,
						'page' =>  $page
				]
		] );
	}
	
	
}