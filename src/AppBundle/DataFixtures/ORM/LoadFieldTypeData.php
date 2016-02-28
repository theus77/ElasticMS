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
				['Container', 'dataField', '', 0, 0, 0, ''],
				['Ouuid', 'key', 'key', 0, 0, 0, '{"icon":"fa fa-key"}'],
				['Container', 'translations', 'Translations', 0, 0, 0, '{"icon":"fa fa-language"}'],
				['String', 'value_en', 'English', 0, 0, 0, ''],
				['String', 'value_fr', 'Français', 0, 0, 0, ''],
				['String', 'value_nl', 'Nederlands', 0, 0, 0, ''],
		);
		$richTextFields = array(
				['Container', 'dataField', '', 0, 0, 0, ''],
				['Ouuid', 'key', 'key', 0, 0, 0, '{"icon":"fa fa-key"}'],
				['Container', 'translations', 'Translations', 0, 0, 0, '{"icon":"fa fa-language"}'],
				['Wysiwyg', 'value_en', 'English', 0, 0, 0, ''],
				['Wysiwyg', 'value_fr', 'Français', 0, 0, 0, ''],
				['Wysiwyg', 'value_nl', 'Nederlands', 0, 0, 0, ''],
				
		);
		$demoFields = array(
				['Container', 'dataField', '', 0, 0, 0, ''],
				['Container', 'testContainer', 'Label container', 0, 0, 0, '{"icon":"fa fa-language","label":"with icon"}'],
				['Ouuid', 'key', 'key', 0, 0, 0, '{"icon":"fa fa-key"}'],
				['Container', 'testContainer2', 'Label container', 0, 0, 0, '{"label":"without icon"}'],
				['String', 'testString', 'Test string', 0, 0, 0, ''],
				['String', 'testStringIcon', 'Test string', 0, 0, 0, '{"icon":"fa fa-question","label":"with icon"}'],
				['TextArea', 'tesTextArea', 'Textarea', 0, 0, 0, ''],
				['Wysiwyg', 'testWysiwyg', 'WYSIWYG', 0, 0, 0, ''],
				
		);
		
		$fields = array(
				'label' => $labelFields,
				'rich-text' => $richTextFields,
				'demo' => $demoFields,
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
				if ($type == 'Container'){
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
	
	private function createFieldType($contentType, $parent, $type, $name, $label, $deleted, $orderKey, $many, $editOptions)
	{
		$fieldType = new FieldType();
		if ($parent != ''){
			$fieldType->setParent($this->getReference($parent));
		}else{
			$fieldType->setContentType($this->getReference($contentType));
			$this->getReference($contentType)->setFieldType($fieldType);
		}
		$fieldType->setType('AppBundle\\Form\\DataField\\'.$type.'Type');
		$fieldType->setName($name);
		$fieldType->setLabel($label);
		$fieldType->setDeleted($deleted);
		$fieldType->setOrderKey($orderKey);
		$fieldType->setMany($many);
		$fieldType->setEditOptions($editOptions);
		
		return $fieldType;
	}
}