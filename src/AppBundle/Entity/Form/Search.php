<?php
namespace AppBundle\Entity\Form;


/**
 * RebuildIndex
 */
class Search
{
	private $filters;
	private $boolean;
	
	function __construct(){
		$this->filters = [];//new \Doctrine\Common\Collections\ArrayCollection();
		$this->filters[] = new SearchFilter();
	}

	
	public function addFilter(SearchFilter $filter)
	{
		$this->filters[] = $filter;
	
		return $this;
	}


	public function getFilters()
	{
		return $this->filters;
	}
	
	
	public function setFilters($filters)
	{
		$this->filters = $filters;
	
		return $this;
	}


	public function getBoolean()
	{
		return $this->boolean;
	}
	
	
	public function setBoolean($boolean)
	{
		$this->boolean = $boolean;
	
		return $this;
	}
	
}