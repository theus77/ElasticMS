<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use AppBundle\Entity\Environment;

class LoadEnvironmentData extends AbstractFixture implements OrderedFixtureInterface
{
	//private $currentTime;
	
	public function load(ObjectManager $manager)
	{
		//$this->currentTime =  time();
		
		$environmentsData = array(
				//name, 	color, 		managed
				['preview', 'lightblue',1],
				['staging', 'blue', 	1],
				['live', 	'green', 	1],
				['aperture','red', 		0],
		);

		foreach ($environmentsData as $environmentData)
		{
			$environment = $this->createEnvironment(...$environmentData);
			$manager->persist($environment);
			$manager->flush();

			$name = $environmentData[0];
			$this->addReference($name, $environment);
		}

	
	}
	
	public function getOrder()
	{
		// the order in which fixtures will be loaded
		// the lower the number, the sooner that this fixture is loaded
		return 1;
	}
	
	private function createEnvironment($name, $color, $managed)
	{
		$environment = new Environment();
		$environment->setName($name);
		$environment->setColor($color);
		$environment->setManaged($managed);
		
		return $environment;
	}
}