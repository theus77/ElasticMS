<?php

namespace AppBundle\Form\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ColorPickerType extends SelectPickerType {
	
	private $choices = [
		 'not-defined' => null,
		 'red' => 'red',
		 'maroon' => 'maroon',
		 'orange' => 'orange',
		 'yellow' => 'yellow',
		 'green' => 'green',
		 'teal' => 'teal',
		 'aqua' => 'aqua',
		 'light-blue' => 'light-blue',
		 'blue' => 'blue',
		 'purple' => 'purple',
		 'navy' => 'navy',
		 'black' => 'black',
		 'grey' => 'grey',
		 'olive' => 'olive',
		 'lime' => 'lime',
		'fuchsia' => 'fuchsia',
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
