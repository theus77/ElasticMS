<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AppBundle\Service;


use PHPUnit\Framework\TestCase;
use AppBundle\Service\UserService;
use AppBundle\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\Common\Persistence\ObjectManager;

class UserServiceTest extends TestCase
{
	
	protected function setUp() {
		parent::setUp();
	}
	
	public function testGetUnknowdUser() {
		$mockRepo = $this->prophesize(UserRepository::class);
		$mockRepo->findOneBy(["usernameCanonical" => "user"])->willReturn(null)->shouldBeCalledTimes(1);

		$mockObjectManager = $this->prophesize(ObjectManager::class);
		$mockObjectManager->getRepository('AppBundle:User')->willReturn($mockRepo->reveal())->shouldBeCalledTimes(1);
		
		$mockRegistry = $this->prophesize(Registry::class);
		$mockRegistry->getManager()->willReturn($mockObjectManager->reveal())->shouldBeCalledTimes(1);
		
		$mockSession = $this->prophesize(Session::class);
		
		$mockTokenInterface = $this->prophesize(TokenStorageInterface::class);		
		
		$userService = new UserService($mockRegistry->reveal(), $mockSession->reveal(), $mockTokenInterface->reveal(), null);
		
		$userService->getUser('user');
	}
}