<?php
namespace AppBundle\Entity\Form;


/**
 * RebuildIndex
 */
class SearchFilter
{
	private $pattern;
	private $field;
	private $inverted;
	private $operator;
	private $boost;
	
	function generateEsFilter(){
		$out = false;
		if($this->field || $this->pattern) {
			
			$field = $this->field;
// 			if($this->field){
// 				$path = explode('.', $this->field);
// 				$field = $path[count($path)-1];
// 			}
			
			
			switch ($this->operator){
				case 'match_and':
					$out = [
						"match" => [
								$field?$field:"_all" => [
								"query" =>  $this->pattern?$this->pattern:"",
								"operator" => "AND",
								"boost" => $this->boost?$this->boost:1,
							]
						]
					];
					break;
				case 'match_or':
					$out = [
						"match" => [
								$field?$field:"_all" => [
								"query" =>  $this->pattern?$this->pattern:"",
								"operator" => "OR",
								"boost" => $this->boost?$this->boost:1,
							]
						]
					];
					break;
				case 'query_and':
					$out = [
						"query_string" => [
							"default_field" => $field?$field:"_all",
							"query" =>  $this->pattern?$this->pattern:"*",
							"default_operator" => "AND",
							"boost" => $this->boost?$this->boost:1,
						]
					];
					break;
				case 'query_or':
					$out = [
						"query_string" => [
							"default_field" => $field?$field:"_all",
							"query" =>  $this->pattern?$this->pattern:"*",
							"default_operator" => "OR",
							"boost" => $this->boost?$this->boost:1,
						]
					];
					break;
			}
			
			if($this->field){
				$path = explode('.', $this->field);
				for($i=count($path)-2; $i >= 0; --$i){
					$out = [
						"nested" => [
								"path" => $path[$i],
								"query" => $out,
						]
					];
				}
			}
			if($this->inverted){
				$out = ['not' => $out];
			}
		}		
		
		return $out;
	}

    /**
     * Get pattern
     *
     * @return string
     */
	public function getPattern(){
		return $this->pattern;
	}	
	/**
	 * Set pattern
	 * 
	 * @param string $pattern
	 */
	public function setPattern($pattern){
		$this->pattern = $pattern;
		return $this;
	}

    /**
     * Get field
     *
     * @return string
     */
	public function getField(){
		return $this->field;
	}	
	/**
	 * Set field
	 * 
	 * @param string $field
	 */
	public function setField($field){
		$this->field = $field;
		return $this;
	}

    /**
     * Get inverted
     *
     * @return boolean
     */
	public function getInverted(){
		return $this->inverted;
	}	
	/**
	 * Set inverted
	 * 
	 * @param boolean $inverted
	 */
	public function setInverted($inverted){
		$this->inverted = $inverted;
		return $this;
	}

	/**
	 * Get operator
	 *
	 * @return string
	 */
	public function getOperator(){
		return $this->operator;
	}
	/**
	 * Set operator
	 *
	 * @param string $operator
	 */
	public function setOperator($operator){
		$this->operator = $operator;
		return $this;
	}

	/**
	 * Get boost
	 *
	 * @return float
	 */
	public function getBoost(){
		return $this->boost;
	}
	/**
	 * Set boost
	 *
	 * @param float $boost
	 */
	public function setBoost($boost){
		$this->boost = $boost;
		return $this;
	}
	
}