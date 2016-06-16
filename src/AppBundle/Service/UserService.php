<?php

namespace AppBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserService {
	/**@var Registry $doctrine */
	private $doctrine;
	/**@var Session $session*/
	private $session;
	/**@var TokenStorageInterface $tokenStorage */
	private $tokenStorage;
	
	private $currentUser;
	
	public function __construct(Registry $doctrine, Session $session, TokenStorageInterface $tokenStorage) {
		$this->doctrine = $doctrine;
		$this->session = $session;
		$this->tokenStorage = $tokenStorage;
		$this->currentUser = null;
	}
	
	public function getUser($username) {
		$em = $this->doctrine->getManager();
		/**@var \Doctrine\ORM\EntityRepository */
		$repository = $em->getRepository('AppBundle:User');
		$user = $repository->findOneBy([
				'usernameCanonical' => $username
		]);
		
		$em->detach($user);
		
		return $user;
	}
	
	public function getCurrentUser() {
		if(!$this->currentUser){
			$username = $this->tokenStorage->getToken()->getUsername();
			$this->currentUser = $this->getUser($username);
		}
		return $this->currentUser;
	}
	
	
}