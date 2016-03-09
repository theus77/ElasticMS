<?php

namespace AppBundle\Form\DataField;

use AppBundle\Form\Field\AnalyzerPickerType;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\IconTextType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\FieldType;
			
/**
 * Defined a Container content type.
 * It's used to logically groups subfields together. However a Container is invisible in Elastic search.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
 class TextFieldType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Text field';
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {

		/** @var FieldType $fieldType */
		$fieldType = $options ['metadata'];
		
		if($options['prefixIcon'] || $options['prefixText'] || $options['suffixIcon'] || $options['suffixText'] ){
			$builder->add ( 'text_value', IconTextType::class, [ 
					'required' => false,
					'label' => (null != $options ['label']?$options ['label']:' slsls'),
					'icon' => $options['prefixIcon'],
					'prefixText' => $options['prefixText'],
					'suffixIcon' => $options['suffixIcon'],
					'suffixText' => $options['suffixText'],
			] );			
		}
		else{
			$builder->add ( 'text_value', TextType::class, [
					'label' => (null != $options ['label']?$options ['label']:$fieldType->getName()),
					'required' => false,
			] );			
		}
		
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
// 	public function buildView(FormView $view, FormInterface $form, array $options) {
// 		/* get options for twig context */
// // 		parent::buildView ( $view, $form, $options );
// // 		$view->vars ['prefixIcon'] = $options ['prefixIcon'];
// 	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );
		$resolver->setDefault ( 'prefixIcon', null );
		$resolver->setDefault ( 'prefixText', null );
		$resolver->setDefault ( 'suffixIcon', null );
		$resolver->setDefault ( 'suffixText', null );
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
		$optionsForm->get ( 'displayOptions' )->add ( 'prefixIcon', IconPickerType::class, [ 
				'required' => false 
		] )->add ( 'prefixText', IconTextType::class, [ 
				'required' => false,
				'icon' => 'fa fa-hand-o-left' 
		] )->add ( 'suffixIcon', IconPickerType::class, [ 
				'required' => false 
		] )->add ( 'suffixText', IconTextType::class, [ 
				'required' => false,
				'icon' => 'fa fa-hand-o-right' 
		] );
		
		// String specific mapping options
		$optionsForm->get ( 'mappingOptions' )->add ( 'analyzer', AnalyzerPickerType::class);
	}
}