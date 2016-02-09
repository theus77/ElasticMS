<?php

namespace AppBundle\Factories;

use Elasticsearch\ClientBuilder;

/**
 * elasticSearch Factory.
 */
class ElasticsearchClientBuilderFactory
{
	public static function build(){
		$client = ClientBuilder::create();
		//TODO instead of hardcoding a default configuration, use parameters from config.yml which can be different for dev / prod
		$client = $client->build();
		return $client;
	}
}