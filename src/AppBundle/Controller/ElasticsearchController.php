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
			$es_query = json_decode('{
				"size": 50,
			    "query": {
			    	"match_all" : { }
			    } }', true);
		}
		else {
			$es_query = json_decode('
			{
			   "size": 50,
			   "query": {
			      "match": {
			         "_all": {
			            "query": '.json_encode($query).',
			            "operator": "and"
			}}}}', true);	
		}
		
		$client = ClientBuilder::create()->build();
		$results = $client->search(['body' => $es_query]);

// 		dump($results);
	
		return $this->render( 'elasticsearch/search.html.twig', [
 				'query' => $query,
				'results' => $results
		] );
	}
	
	
}