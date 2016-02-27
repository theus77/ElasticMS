<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as TextareaSymfonyType;
use Symfony\Component\Form\FormBuilderInterface;

class TextAreaType extends DataFieldType {
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions () ['metadata'];
		$data = $builder->getData ();
		
		$builder->add ( 'text_value', TextareaSymfonyType::class, [ 
				'label' => $fieldType->getLabel (),
				'required' => false 
		] );
	}
}