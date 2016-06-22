<?php

namespace AppBundle\Form\Form;

use AppBundle\Form\Field\EnvironmentPickerType;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CompareEnvironmentFormType extends AbstractType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {		
		//http://symfony.com/doc/current/cookbook/form/dynamic_form_modification.html#cookbook-dynamic-form-modification-suppressing-form-validation
		$builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
			$event->stopPropagation();
		}, 900);

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