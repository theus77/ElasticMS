<?php

namespace AppBundle\Form\Field;

use AppBundle\Service\EnvironmentService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\Environment;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class EnvironmentPickerType extends ChoiceType {
	
	private $choices;
	private $service;
	
	public function __construct(EnvironmentService $service)
	{
		parent::__construct();
		$this->service = $service;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'selectpicker';
	}
	
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
// 		$this->choices = [];
		$keys = [];
		/**@var Environment $choice*/
		foreach ($this->choices as $key => $choice){
			if($choice->getManaged() || !$options['managedOnly']){
				$keys[] = $choice->getName();
				$this->choices[$choice->getName()] = $choice;
			}
		}		
		$options['choices'] = $keys;
		parent::buildForm($builder, $options);
	}
	
	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$this->choices = [];
		/**@var Environment $choice*/
		foreach ($this->service->getAllInMyCircle() as $choice){	
				$this->choices[$choice->getName()] = $choice;
		}
		parent::configureOptions($resolver);
		
		$resolver->setDefaults(array(
			'choices' => [],
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
		    'managedOnly' => true,
		));
	}
}

