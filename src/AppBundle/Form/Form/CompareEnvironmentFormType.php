<?php

namespace AppBundle\Form\Form;

use AppBundle\Form\Field\EnvironmentPickerType;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompareEnvironmentFormType extends AbstractType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {		
		$builder->setMethod('GET');
		$builder->add('environment', EnvironmentPickerType::class, [
		])->add('withEnvironment', EnvironmentPickerType::class, [
		])->add('compare', SubmitEmsType::class, [
				'attr' => [ 
						'class' => 'btn-primary btn-md' 
				],
				'icon' => 'fa fa-columns'
		]);
	}
}