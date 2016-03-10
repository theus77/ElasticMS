<?php

namespace AppBundle\Form\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldTypePickerType extends SelectPickerType {
	//TODO: choices list should be generated (symfony service tag?)

	private $choices = [
			'AppBundle\Form\DataField\ContainerFieldType',
			'AppBundle\Form\DataField\OuuidFieldType',
			'AppBundle\Form\DataField\TextFieldType',
			'AppBundle\Form\DataField\WysiwygFieldType',
			'AppBundle\Form\DataField\TextareaFieldType',
			'AppBundle\Form\DataField\SelectFieldType',
			'AppBundle\Form\DataField\PasswordFieldType',
			'AppBundle\Form\DataField\EmailFieldType',
			'AppBundle\Form\DataField\RadioFieldType',
			'AppBundle\Form\DataField\ChoiceFieldType',
			'AppBundle\Form\DataField\CheckboxFieldType',
	];
	
	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'choices' => $this->choices,
			'attr' => [
					'data-live-search' => true
			],
			'choice_attr' => function($category, $key, $index) {
				/** @var \AppBundle\Form\DataField\DataFieldType $dataFieldType */
				$dataFieldType = new $index;
				return [
						'data-content' => "<div class='text-".$category."'><i class='fa fa-square'></i>&nbsp;&nbsp;".$dataFieldType->getLabel().'</div>'
				];
			},
			'choice_value' => function ($value) {
		       return $value;
		    },
		));
	}
}
