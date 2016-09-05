<?php

namespace AppBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserService {
	/**@var Registry $doctrine */
	private $doctrine;
	/**@var Session $session*/
	private $session;
	/**@var TokenStorageInterface $tokenStorage */
	private $tokenStorage;
	
	private $currentUser;
	
	private $securityRoles;
	
	public function __construct(Registry $doctrine, Session $session, TokenStorageInterface $tokenStorage, $securityRoles) {
		$this->doctrine = $doctrine;
		$this->session = $session;
		$this->tokenStorage = $tokenStorage;
		$this->currentUser = null;
		$this->securityRoles = $securityRoles;
	}
	
	
	public function findUsernameByApikey($apiKey){
		$em = $this->doctrine->getManager();
		/**@var \Doctrine\ORM\EntityRepository */
		$repository = $em->getRepository('AppBundle:User');
		
		/**@var User $user*/
		$user = $repository->findOneBy([
				'apiKey' => $apiKey
		]);
		if(empty($user)){
			return null;
		}
		return $user->getUsername();
		
	}
	
	public function getUser($username, $detachIt = true) {
		$em = $this->doctrine->getManager();
		/**@var \Doctrine\ORM\EntityRepository */
		$repository = $em->getRepository('AppBundle:User');
		$user = $repository->findOneBy([
				'usernameCanonical' => $username
		]);
		
		if($detachIt) {
			$em->detach($user);			
		}
		
		return $user;
	}
	
	/**
	 * @return User
	 */
	public function getCurrentUser() {
		if(!$this->currentUser){
			$username = $this->tokenStorage->getToken()->getUsername();
			$this->currentUser = $this->getUser($username);
		}
		return $this->currentUser;
	}
	
	
	public function getUsersForRoleAndCircles($role, $circles) {
		/**@var EntityManagerInterface $em*/
		$em = $this->doctrine->getManager();
		$repository = $em->getRepository('AppBundle:User');
		return $repository->findForRoleAndCircles($role, $circles);
	}
	
	
	public function getAllUsers() {
		$em = $this->doctrine->getManager();
		/**@var \Doctrine\ORM\EntityRepository */
		$repository = $em->getRepository('AppBundle:User');
		return $repository->findBy([
				'enabled' => true
		]);
	}
	
	public  function getsecurityRoles() {
		return $this->securityRoles;
	}

}