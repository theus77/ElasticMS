<?php

namespace AppBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Session\Session;
use AppBundle\Entity\ContentType;

class ContentTypeService {
	/**@var Registry $doctrine */
	protected $doctrine;
	/**@var Session $session*/
	protected $session;
	
	protected $orderedContentTypes;
	protected $contentTypeArrayByName;
	
	
	public function __construct(Registry $doctrine, Session $session)
	{
		$this->doctrine = $doctrine;
		$this->session = $session;
		$this->orderedContentTypes = false;
		$this->contentTypeArrayByName = false;
	}
	
	private function loadEnvironment(){
		if($this->orderedContentTypes === false) {
			$this->orderedContentTypes = $this->doctrine->getManager()->getRepository('AppBundle:ContentType')->findBy(['deleted' => false], ['orderKey' => 'ASC']);
			$this->contentTypeArrayByName = [];
			/**@var ContentType $contentType */
			foreach ($this->orderedContentTypes as $contentType) {
				$this->contentTypeArrayByName[$contentType->getName()] = $contentType;
			}
		}
	}
	
	/**
	 * 
	 * @param string $name
	 * @return ContentType
	 */
	public function getByName($name){
		$this->loadEnvironment();
		if(isset($this->contentTypeArrayByName[$name])){
			return $this->contentTypeArrayByName[$name];
		}
		return false;
	}



	/**
	 *
	 * @param string $name
	 * @return ContentType
	 */
	public function getAllByAliases(){
		$this->loadEnvironment();
		$out = [];
		/**@var ContentType $contentType */
		foreach ($this->orderedContentTypes as $contentType){
			if(!isset( $out[$contentType->getEnvironment()->getAlias()] )){
				$out[$contentType->getEnvironment()->getAlias()] = [];
			}
			$out[$contentType->getEnvironment()->getAlias()][$contentType->getName()] = $contentType;
		}
		return $out;
	}
	
	
	/**
	 *
	 */
	public function getAllAliases(){
		$this->loadEnvironment();
		$out = [];
		/**@var ContentType $contentType */
		foreach ($this->orderedContentTypes as $contentType){
			if(!isset( $out[$contentType->getEnvironment()->getAlias()] )){
				$out[$contentType->getEnvironment()->getAlias()] = $contentType->getEnvironment()->getAlias();
			}
		}
		return implode(',', $out);
	}

	/**
	 * 
	 * @return string
	 */
	 public function getAllTypes(){
		$this->loadEnvironment();
		return implode(',', array_keys($this->contentTypeArrayByName));
	}
	
}