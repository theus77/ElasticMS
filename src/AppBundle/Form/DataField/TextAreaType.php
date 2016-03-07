<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as TextareaSymfonyType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\FieldType\StringOptionsType;

class TextAreaType extends DataFieldType {
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
				'label' => $fieldType->getName(),
		], $fieldType->getDisplayOptions());
		
		$builder->add ( 'text_value', TextareaSymfonyType::class, [
				'label' => $options['label']
		]);
	}
	
    public static function getOptionsFormType(){
    	return StringOptionsType::class;
    }
}