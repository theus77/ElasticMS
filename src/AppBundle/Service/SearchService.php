<?php

namespace AppBundle\Service;


use AppBundle\Entity\Form\Search;
use AppBundle\Entity\Form\SearchFilter;

class SearchService
{
	
	public function __construct() {
	}
	
	public function generateSearchBody(Search $search){
		$body = [];
		

		/** @var SearchFilter $filter */
		foreach ($search->getFilters() as $filter){
				
			$esFilter = $filter->generateEsFilter();
		
			if($esFilter){
				$body["query"]["bool"][$filter->getBooleanClause()][] = $esFilter;
			}
				
		}
		if ( null != $search->getSortBy() && strlen($search->getSortBy()) > 0  ) {
			$body["sort"] = [
					$search->getSortBy() => [
							'order' => $search->getSortOrder(),
							'missing' => '_last',
					]
			];
		
		}
		return $body;
	} 
	
	
}