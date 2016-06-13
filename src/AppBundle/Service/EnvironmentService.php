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
	}
	
	private function loadEnvironment(){
		if($this->environments === false) {
			$environments = $this->doctrine->getManager()->getRepository('AppBundle:Environment')->findAll();
			$this->environments = [];
			/**@var \AppBundle\Entity\Environment $environment */
			foreach ($environments as $environment) {
				$this->environments[$environment->getName()] = $environment;
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
	
	
}