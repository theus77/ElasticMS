<?php

namespace AppBundle\Form\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ColorPickerType extends SelectPickerType {
	
	private $choices = [
		 'not-defined' => null,
// 		 'danger' => 'danger',
// 		 'warning' => 'warning',
		 'red' => 'red',
		 'maroon' => 'maroon',
		 'orange' => 'orange',
		 'yellow' => 'yellow',
// 		 'success' => 'success',
		 'green' => 'green',
		 'teal' => 'teal',
		 'aqua' => 'aqua',
		 'light-blue' => 'light-blue',
		 'blue' => 'blue',
// 		 'primary' => 'primary',
// 		 'info' => 'info',
		 'purple' => 'purple',
		 'navy' => 'navy',
		 'black' => 'black',
		 'grey' => 'grey',
// 		 'muted' => 'muted',
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
