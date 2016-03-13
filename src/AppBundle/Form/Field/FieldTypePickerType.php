<?php

namespace AppBundle\Form\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldTypePickerType extends SelectPickerType {
	
	private $dataFieldTypes;
	
	public function __construct()
	{
		parent::__construct();
		$this->dataFieldTypes = array();
	}
	
	public function addDataFieldType($dataField, $dataFieldTypeId)
	{
		$this->dataFieldTypes[$dataFieldTypeId] = $dataField;
	}
	
	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		dump($this->dataFieldTypes);
		
		$resolver->setDefaults(array(
			'choices' => array_keys($this->dataFieldTypes),
			'attr' => [
					'data-live-search' => true
			],
			'choice_attr' => function($category, $key, $index) {
				/** @var \AppBundle\Form\DataField\DataFieldType $dataFieldType */
				$dataFieldType = $this->dataFieldTypes[$index];
				return [
						'data-content' => '<div class="text-'.$category.'"><i class="'.$dataFieldType->getIcon().'"></i>&nbsp;&nbsp;'.$dataFieldType->getLabel().'</div>'
				];
			},
			'choice_value' => function ($value) {
		       return $value;
		    },
		));
	}
}
