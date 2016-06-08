<?php

namespace AppBundle\Form\Field;

use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

class ObjectPickerType extends Select2Type {
	/**@var ObjectChoiceLoader $choiceLoader*/
	private $choiceLoader;

	
	public function __construct(ChoiceListFactoryInterface $factory){
		parent::__construct($factory);
		$this->choiceLoader = $factory->createLoader();
	}
	
	/**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		if(!$options['dynamicLoading']){
			$this->choiceLoader->loadAllChoices($options['environment'], $options['type']);
			$options['choices'] = $this->choiceLoader->loadChoiceList()->getChoices();
			$options['choice_loader'] = null;
		}
		else{
			$options['choice_loader'] = $this->choiceLoader;
		}
		parent::buildForm ( $builder, $options );
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
			'choice_loader' => $this->choiceLoader,
		    'choice_label' => function ($value, $key, $index) {
		    	return $value->getLabel($key);
		    },
		    'group_by' => function($val, $key, $index) {
// 		    	TODO choice list group by
			    return 'other';
		    },
			'choice_value' => function ($value) {
				if(is_string($value)){
					return $value;
				}
				$object = $value->getObject();
		       return $object['_type'].':'.$object['_id'];
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
