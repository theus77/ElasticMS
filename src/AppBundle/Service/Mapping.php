<?php

namespace AppBundle\Service;


use AppBundle\Entity\ContentType;
use AppBundle\Form\Field\FieldTypePickerType;
use AppBundle\Form\FieldType\FieldTypeType;
use AppBundle\Entity\DataField;

/**
 * elasticSearch Factory.
 */
class Mapping
{

	/** @var FieldTypeType $fieldTypeType */
	private $fieldTypeType;
	
	public function __construct(FieldTypeType $fieldTypeType) {
		$this->fieldTypeType = $fieldTypeType;
	}
	
	public function generateMapping(ContentType $contentType){
		$out = [
				$contentType->getName() => [
						"_all" => [
								"store" => true,
								"enabled" => true,
						],
							"properties" => [
						],
				],
		];
		
		if(null != $contentType->getFieldType()){
			$out[$contentType->getName()]['properties'] = $this->fieldTypeType->generateMapping($contentType->getFieldType());
		}
		return $out;
	} 



	public function dataFieldToArray(DataField $dataField){
		return $this->fieldTypeType->dataFieldToArray($dataField);
	}	
	
	
}