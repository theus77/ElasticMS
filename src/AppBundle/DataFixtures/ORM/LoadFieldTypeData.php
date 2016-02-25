<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\FieldType;
use Doctrine\Common\DataFixtures\AbstractFixture;

class LoadFieldTypeData extends AbstractFixture implements OrderedFixtureInterface
{
	//private $currentTime;
	
	public function load(ObjectManager $manager)
	{
		//$this->currentTime = time(); //date("Y-m-d H:i:s");
		//Create fields for CT label
		$labelFields = array(
				//contentType id, parent id --> should both be set dynamically
				//type, name, label, deleted, orderKey, many, icon
				['Container', 'datafield', '', 0, 0, 0, ''],
				['Ouuid', 'key', 'key', 0, 0, 0, 'fa fa-key'],
				['Container', 'translations', 'Translations', 0, 0, 0, 'fa fa-language'],
				['String', 'value_en', 'English', 0, 0, 0, ''],
				['String', 'value_fr', 'Français', 0, 0, 0, ''],
				['String', 'value_nl', 'Nederlands', 0, 0, 0, ''],
		);
		$richTextFields = array(
				['Container', 'datafield', '', 0, 0, 0, ''],
		);
		
		$fields = array(
				'label' => $labelFields,
				'rich-text' => $richTextFields,
		);
		
		foreach ($fields as $contentTypeName => $data)
		{
			//keep a reference to the last created container (will be used as parent)
			$containerField = null;
			foreach ($data as $fieldData)
			{
				$fieldType = $this->createFieldType($contentTypeName, $containerField, ...$fieldData);
				$manager->persist($fieldType);
				$manager->flush();
				
				//If container this field will be the new parent for the next fields
				//TODO or not?: this does not work for recursive containers, except in a straight line (= the order of your fields is very importan in this strategy)
				$type = $fieldData[0];
				if ($type == "Container"){
					$name = $fieldData[2];
					$containerField = $type.$name;
					//TODO This looks ugly .-)--> I'm a pirate arrr
					try {
						$this->getReference($containerField);
						//if no error occured we need to override the reference
						$this->setReference($containerField, $fieldType);
					} catch (\OutOfBoundsException $e) {
						//if error occured the reference does not exist and we can add it
						$this->addReference($containerField, $fieldType);
					}
				}
			}
		}

	
	}
	
	public function getOrder()
	{
		// the order in which fixtures will be loaded
		// the lower the number, the sooner that this fixture is loaded
		return 3;
	}
	
	private function createFieldType($contentType, $parent, $type, $name, $label, $deleted, $orderKey, $many, $icon)
	{
		$fieldType = new FieldType();
		//$fieldType->setContentType($this->getReference($contentType));
		//$fieldType->setParent($this->getReference($parent));
		//$fieldType->setCreated($this->currentTime);
		//$fieldType->setModified($this->currentTime);
		$fieldType->setType('AppBundle\\Form\\'.$type.'Type');
		$fieldType->setName($name);
		$fieldType->setLabel($label);
		$fieldType->setDeleted($deleted);
		$fieldType->setOrderKey($orderKey);
		$fieldType->setMany($many);
		$fieldType->setIcon($icon);
		
		return $fieldType;
	}
}