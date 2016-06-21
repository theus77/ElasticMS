<?php

namespace AppBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Session\Session;

class EnvironmentService {
	/**@var Registry $doctrine */
	protected $doctrine;
	/**@var Session $session*/
	protected $session;
	
	protected $environments;
	
	
	public function __construct(Registry $doctrine, Session $session)
	{
		$this->doctrine = $doctrine;
		$this->session = $session;
		$this->environments = false;
		$this->byId = false;
	}
	
	private function loadEnvironment(){
		if($this->environments === false) {
			$environments = $this->doctrine->getManager()->getRepository('AppBundle:Environment')->findAll();
			$this->environments = [];
			$this->byId = [];
			/**@var \AppBundle\Entity\Environment $environment */
			foreach ($environments as $environment) {
				$this->environments[$environment->getName()] = $environment;
				$this->byId[$environment->getId()] = $environment;
			}
		}
	}
	
	public function getAliasByName($name){
		$this->loadEnvironment();
		if(isset($this->environments[$name])){
			return $this->environments[$name];
		}
		return false;
	}	
	
	public function getById($id){
		$this->loadEnvironment();
		if(isset($this->byId[$id])){
			return $this->byId[$id];
		}
		return false;
	}


	public function getAll(){
		$this->loadEnvironment();
		return $this->environments;
	}
	
	
}