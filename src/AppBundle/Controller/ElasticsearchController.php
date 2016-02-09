<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Elasticsearch\ClientBuilder;

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
	public function searchAction($query, Request $request)
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
		$results = $client->search(['body' => $es_query, 'version' => true, 'size' => 50]);

		dump($results);
		dump(json_decode($es_query,true));
	
		return $this->render( 'elasticsearch/search.html.twig', [
 				'query' => $query,
				'results' => $results
		] );
	}
	
	
}