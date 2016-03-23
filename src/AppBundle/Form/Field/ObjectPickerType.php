<?php

namespace AppBundle\Form\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;

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
			'attr' => [
					'data-remote-url' => '/search.json'
			],
			'choice_attr' => function($category, $key, $index) {
				dump('choice_attr');
				dump($key);
				return [
						'data-content' => json_encode($key)
				];
			},
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
				dump('choice_value');
				dump($value);
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
	public function getBlockPrefix() {
		return 'objectpicker';
	}
	
}
