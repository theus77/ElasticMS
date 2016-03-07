<?php 

namespace AppBundle\Form\FieldType;

use AppBundle\Entity\FieldType;

class OuuidOptionsType extends DataFieldOptionsType
{


	public static function generateMapping(array $options, FieldType $current){
		return [$current->getName() =>[
			'type' => 'string',
			'index' => 'not_analyzed'
		]];
	}

}