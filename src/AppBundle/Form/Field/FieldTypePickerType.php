<?php

namespace AppBundle\Form\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldTypePickerType extends SelectPickerType {
	

	private $choices = [
			'Container' => 'AppBundle\Form\DataField\ContainerType',
			'Ouuid' => 'AppBundle\Form\DataField\OuuidType',
			'String' => 'AppBundle\Form\DataField\StringType',
			'WYSIWYG' => 'AppBundle\Form\DataField\WysiwygType',
			'TextArea' => 'AppBundle\Form\DataField\TextAreaType',
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
				return [
						'data-content' => "<div class='text-".$category."'><i class='fa fa-square'></i>&nbsp;&nbsp;".$this->humanize($key).'</div>'
				];
			},
			'choice_value' => function ($value) {
		       return $value;
		    },
		));
	}
}
