<?php

namespace AppBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Session\Session;
use AppBundle\Entity\ContentType;
use Elasticsearch\Client;

class ContentTypeService {
	/**@var Registry $doctrine */
	protected $doctrine;
	/**@var Session $session*/
	protected $session;
	
	/**@var Mapping*/
	private $mappingService;
	
	/**@var Client*/
	private $client;
	
	/**@var EnvironmentService $environmentService */
	private $environmentService;
	
	protected $orderedContentTypes;
	protected $contentTypeArrayByName;
	
	
	
	public function __construct(Registry $doctrine, Session $session, Mapping $mappingService, Client $client, EnvironmentService $environmentService)
	{
		$this->doctrine = $doctrine;
		$this->session = $session;
		$this->orderedContentTypes = false;
		$this->contentTypeArrayByName = false;
		$this->mappingService = $mappingService;
		$this->client = $client;
		$this->environmentService = $environmentService;
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
	
	public function persist(ContentType $contentType){
		$em = $this->doctrine->getManager();
		$em->persist($contentType);
		$em->flush();
	}
	
	public function updateMapping(ContentType $contentType){
		try {	
			
			$envs = array_reduce ( $this->environmentService->getManagedEnvironement(), function ($envs, $item) {
				/**@var \AppBundle\Entity\Environment $item*/
				if (isset ( $envs )) {
					$envs .= ',' . $item->getAlias();
				} else {
					$envs = $item->getAlias();
				}
				return $envs;
			} );
			
			$out = $this->client->indices()->putMapping ( [
					'index' => $envs,
					'type' => $contentType->getName (),
					'body' => $this->mappingService->generateMapping ($contentType)
			] );
				
			if (isset ( $out ['acknowledged'] ) && $out ['acknowledged']) {
				$contentType->setDirty ( false );
				$this->session->getFlashBag()->add ( 'notice', 'Mappings successfully updated' );
			} else {
				$contentType->setDirty ( true );
				$this->session->getFlashBag()->add ( 'warning', '<p><strong>Something went wrong. Try again</strong></p>
						<p>Message from Elasticsearch: ' . print_r ( $out, true ) . '</p>' );
			}
				
		} catch ( BadRequest400Exception $e ) {
			$contentType->setDirty ( true );
			$message = json_decode($e->getPrevious()->getMessage(), true);
			$this->session->getFlashBag()->add ( 'error', '<p><strong>You should try to rebuild the indexes</strong></p>
					<p>Message from Elasticsearch: <b>' . $message['error']['type']. '</b>'.$message['error']['reason'] . '</p>' );
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