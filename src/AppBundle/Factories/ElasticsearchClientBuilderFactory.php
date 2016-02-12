<?php

namespace AppBundle\Factories;

use Elasticsearch\ClientBuilder;

/**
 * elasticSearch Factory.
 */
class ElasticsearchClientBuilderFactory
{
	public static function build($cluster){
		$client = ClientBuilder::create()	// Instantiate a new ClientBuilder			
				->setHosts($cluster)      // Set the hosts
				->build();              // Build the client object

		return $client;
	}	
}