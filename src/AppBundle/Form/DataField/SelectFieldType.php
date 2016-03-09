<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as ChoiceSymfonyType;
use Symfony\Component\Form\FormBuilderInterface;

class SelectFieldType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Select field';
	}
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions () ['metadata'];
		
		$choices = $fieldType->getEditOptionsArray() ['choices'];
		//TODO check for required choices, multiple selection, ...
		$builder->add ( 'text_value', ChoiceSymfonyType::class, [ 
				'label' => $fieldType->getLabel (),
				'required' => false,
				'choices' => $choices
		] );
	}
}