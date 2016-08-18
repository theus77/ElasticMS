<?php

namespace AppBundle\Form\Field;

use AppBundle\Service\EnvironmentService;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvironmentPickerType extends SelectPickerType {
	
	private $choices;
	private $service;
	
	public function __construct(EnvironmentService $service)
	{
		parent::__construct();
		$this->service = $service;
	}
	
	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		
		$this->choices = $this->service->getAllInMyCircle();
		
		$keys = [];
		foreach ($this->choices as $choice){
			$keys[] = $choice->getId();
		}
		
		$resolver->setDefaults(array(
			'choices' => array_keys($this->choices),
			'attr' => [
					'data-live-search' => false
			],
			'choice_attr' => function($category, $key, $index) {
				/** @var \AppBundle\Form\DataField\DataFieldType $dataFieldType */
				$dataFieldType = $this->choices[$index];
				return [
						'data-content' => '<span class="text-'.$dataFieldType->getColor().'"><i class="fa fa-square"></i>&nbsp;&nbsp;'.$dataFieldType->getName().'</span>'
				];
			},
			'choice_value' => function ($value) {
				return $value;
		    },
		    'multiple' => false,
		));
	}
}

