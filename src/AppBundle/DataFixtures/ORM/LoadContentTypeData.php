<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\ContentType;
use Doctrine\Common\DataFixtures\AbstractFixture;

class LoadContentTypeData extends AbstractFixture implements OrderedFixtureInterface
{
	//private $currentTime;
	
	public function load(ObjectManager $manager)
	{
		//set globals
		//$this->currentTime = time(); //date("Y-m-d H:i:s");
		
		
		//Define twig code outside the data array (for readability purposes)
		$labelTwig = '<ul>	
	<li>Key: {{ source.key }}</li>
	<li>English: <b>{{ source.value_en }}</b></li>
	<li>French: <b>{{ source.value_fr }}</b></li>
	<li>Nederlands: <b>{{ source.value_nl }}</b></li>
</ul>';
		$richTextTwig = '';
		
		//define content type specific fields
		$data = array(
				//name, pluralName, icon, alias, description, indexTwig, color, rootContentType, active, environment_id
				['label', 'labels', 'fa fa-key', 'draft', 'Translation keys', $labelTwig, 'red', 0, 1, 1, 'preview'],
				['rich-text', 'rich-texts', 'fa fa-html5', 'draft', 'WYSIWYG fields', $richTextTwig, 'purple', 0, 1, 1, 'preview'],
		);
	
		foreach ($data as $contentData)
		{
			$contentType = $this->createContentType(...$contentData);
			$manager->persist($contentType);
			$manager->flush();
			
			$name = $contentData[0];
			$this->addReference($name, $contentType); //Enable a fieldType to reference this contentType	
		}
	}
	
	public function getOrder()
	{
		// the order in which fixtures will be loaded
		// the lower the number, the sooner that this fixture is loaded
		return 2;
	}
	
	
	private function createContentType($name, $pluralName, $icon, $alias, $description, $indexTwig, $color, $orderKey, $rootContentType, $active, $environment)
	{
		$contentType = new ContentType();
		//$contentType->setFieldType(); cannot be set here, will be referenced in LoadFieldTypeData.php
		//$contentType->setCreated($this->currentTime);
		//$contentType->setModified($this->currentTime);
		$contentType->setName($name);
		$contentType->setPluralName($pluralName);
		$contentType->setIcon($icon);
		$contentType->setAlias($alias);
		$contentType->setDescription($description);
		$contentType->setIndexTwig($indexTwig);
		$contentType->setColor($color);
		$contentType->setOrderKey($orderKey);
		$contentType->setRootContentType($rootContentType);
		$contentType->setActive($active);
		$contentType->setEnvironment($this->getReference($environment));
	
		return $contentType;
	}
}