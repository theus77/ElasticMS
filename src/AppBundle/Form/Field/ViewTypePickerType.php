<?php

namespace AppBundle\Form\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ViewTypePickerType extends SelectPickerType {
	//TODO: choices list should be generated (symfony service tag?)

	private $choices = [
			'AppBundle\Form\View\KeywordsViewType',
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
				$viewType = new $index;
				return [
						'data-content' => "<div class='text-".$category."'><i class='fa fa-square'></i>&nbsp;&nbsp;".$viewType->getLabel().'</div>'
				];
			},
			'choice_value' => function ($value) {
		       return $value;
		    },
		));
	}
}
