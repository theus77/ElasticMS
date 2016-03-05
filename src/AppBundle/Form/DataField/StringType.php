<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\FieldType\StringOptionsType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class StringType extends DataFieldType {
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {		
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions()['metadata'];
		
		$options = array_merge([
			'required' => false,
			'label' => $fieldType->getLabel (),
		], $fieldType->getEditOptionsArray());
		
		
		if (isset($options['icon'])) {
			$builder->add ( 'text_value', IconTextType::class, $options );
		} else {
			$builder->add ( 'text_value', TextType::class, $options );
		}
	}
	
    public static function getOptionsFormType(){
    	return StringOptionsType::class;
    }
}