<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\ContentType;
use AppBundle\Repository\ContentTypeRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DefaultControllerTest extends WebTestCase
{
    /**@var Client $client*/
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient();
    }


    public function testDashboardWhenNotAuthentified()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }


    public function testDashboardWhenAuthentifiedAsAdmin()
    {
//         $this->logInAsAdmin();

//         $mockDoctrine = $this->prophesize(Registry::class);

//         $mockContentTypeRepository = $this->prophesize(ContentTypeRepository::class);
//         $mockContentTypeRepository->findAllAsAssociativeArray()->willReturn([
//             'type1' => (new ContentType())->setName('type1'),
//             'type2' => (new ContentType())->setName('type2'),
//         ]);

//         $this->client->getContainer()->set('doctrine', $mockDoctrine);

//         $crawler = $this->client->request('GET', '/content-type');

//         $this->assertEquals(200, $this->client->getResponse()->getStatusCode());


    }

    private function logInAsAdmin()
    {
        $session = $this->client->getContainer()->get('session');

        // the firewall context (defaults to the firewall name)
        $firewall = 'main';

        $token = new UsernamePasswordToken('admin', null, $firewall, array('ROLE_ADMIN'));
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

}
