<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use AppBundle\Form\Field\AnalyzerPickerType;

class SelectFieldType extends DataFieldType {
	
	private $choices;
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Select field';
	}
	
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions () ['metadata'];
		
		$this->choices = explode("\n", str_replace("\r", "", $options['choices']));
		
		$array = $this->choices;
		dump(['toto', 'tata']);
		
		//TODO check for required choices, multiple selection, ...
		$builder->add ( 'text_value', ChoiceType::class, [ 
				'label' => (isset($options['label'])?$options['label']:$fieldType->getName()),
				'required' => false,
				'choices' => $array,
    			'empty_data'  => null,
				'choice_label' => function ($value, $key, $index) {
			        return $value;
			        // or if you want to translate some key
			        // return 'form.choice.'.$key;
			    },
		] );
	}
	

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );
		$resolver->setDefault ( 'choices', [] );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'structuredOptions' );
		
		// String specific display options
		$optionsForm->get ( 'displayOptions' )->add ( 'choices', TextareaType::class, [ 
				'required' => false,
		] );
		
		// String specific mapping options
		$optionsForm->get ( 'mappingOptions' )->add ( 'analyzer', AnalyzerPickerType::class);
	}
}