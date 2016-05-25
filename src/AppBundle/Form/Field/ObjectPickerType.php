<?php

namespace AppBundle\Form\Field;

use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjectPickerType extends Select2Type {
	private $choiceLoader;

	
	public function __construct(ChoiceListFactoryInterface $factory){
		parent::__construct($factory);
		$this->choiceLoader = $factory->createLoader();
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
			'choice_loader' => $this->choiceLoader,
// 			'choice_attr' => function($category, $key, $index) {
// 				return [
// 						'data-content' => json_encode($key)
// 				];
// 			},
		    'choice_label' => function ($value, $key, $index) {
		    	$out = '<i class="'.$key['_typeIcon'].'"></i> ';
		    	
		    	if($key['_labelField'] && isset($key['_source'][$key['_labelField']])){
		    		$out .= $key['_source'][$key['_labelField']];
		    	}
		    	else {
		    		$out .= $key['_id'];
		    	}
		    	
		    	return $out;
		    },
			'choice_value' => function ($value) {
		       return $value;
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
