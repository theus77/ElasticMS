<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Elasticsearch\ClientBuilder;

class ElasticsearchController extends Controller
{
	/**
	 * @Route("/elasticsearch/status", name="elasticsearch.status"))
	 */
	public function statusAction()
	{
		$client = ClientBuilder::create()->build();
		$status = $client->cluster()->health();
		
		return $this->render( 'elasticsearch/status.html.twig', [
				'status' => $status
		] );
	}
	
	/**
	 * @Route("/search/{query}", defaults={"query"=null}, name="elasticsearch.search"))
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
		
		$client = ClientBuilder::create()->build();
		$results = $client->search(['body' => $es_query, 'version' => true, 'size' => 50]);

		dump($results);
		dump(json_decode($es_query,true));
	
		return $this->render( 'elasticsearch/search.html.twig', [
 				'query' => $query,
				'results' => $results
		] );
	}
	
	
}