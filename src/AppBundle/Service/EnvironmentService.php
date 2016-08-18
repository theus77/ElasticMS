<?php

namespace AppBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EnvironmentService {
	/**@var Registry $doctrine */
	private $doctrine;
	/**@var Session $session*/
	private $session;
	
	private $environments;
	
	/**@var UserService $userService*/
	private $userService;
	
	/** @var AuthorizationCheckerInterface $authorizationChecker*/
	private $authorizationChecker;
	
	
	public function __construct(Registry $doctrine, Session $session, UserService $userService, AuthorizationCheckerInterface $authorizationChecker)
	{
		$this->doctrine = $doctrine;
		$this->session = $session;
		$this->userService = $userService;
		$this->authorizationChecker = $authorizationChecker;
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
	
	public function getAllInMyCircle() {
		$this->loadEnvironment();
		$out = [];
		$user = $this->userService->getCurrentUser();
		$isAdmin = $this->authorizationChecker->isGranted('ROLE_ADMIN');
		/**@var \AppBundle\Entity\Environment $environment*/
		foreach ($this->environments as $index => $environment){
			if( empty($environment->getCircles()) || $isAdmin || !empty(array_intersect($user->getCircles(), $environment->getCircles()))) {
				$out[$index] = $environment;
			}
		}
		return $out;
	}
	
	
}