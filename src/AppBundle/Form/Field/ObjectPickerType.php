<?php

namespace AppBundle\Form\Field;

use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

class ObjectPickerType extends Select2Type {
	/**@var ChoiceListFactoryInterface $choiceListFactory*/
	private $choiceListFactory;

	
	public function __construct(ChoiceListFactoryInterface $factory){
		$this->choiceListFactory = $factory;
		parent::__construct($factory);
	}
	
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );
		$resolver->setDefaults(array(
			'required' => false,
			'dynamicLoading' => true,  
			'choice_loader' => function (Options $options) {
				if($options->offsetGet('dynamicLoading')){
			        return $this->choiceListFactory->createDynamicLoader($options->offsetGet('type'));					
				}
				else{
					return $this->choiceListFactory->createStaticLoader($options->offsetGet('type'));
				}
		    },
		    'choice_label' => function ($value, $key, $index) {
		    	return $value->getLabel();
		    },
		    'group_by' => function($value, $key, $index) {
		    	return $value->getGroup();
		    },
			'choice_value' => function ($value) {
				return $value->getValue();
		    },
		    'multiple' => false,
		    'type' => null ,
		    'environment' => null,
		    
		));
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		$view->vars ['attr']['data-environment'] = $options['environment'];
		$view->vars ['attr']['data-type'] = $options['type'];
		$view->vars ['attr']['data-dynamic-loading'] = $options['dynamicLoading'];
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'objectpicker';
	}
	
}
