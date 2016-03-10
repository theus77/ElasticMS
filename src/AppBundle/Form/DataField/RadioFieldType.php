<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use AppBundle\Form\Field\AnalyzerPickerType;

class RadioFieldType extends DataFieldType {

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Radio field';
	}
	
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
	
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions () ['metadata'];
	
		$choices = [];
		$values = explode("\n", str_replace("\r", "", $options['choices']));
		$labels = explode("\n", str_replace("\r", "", $options['labels']));
	
		foreach ($values as $id => $value){
			if(isset($labels[$id])){
				$choices[$labels[$id]] = $value;
			}
			else {
				$choices[$value] = $value;
			}
		}
	
		$builder->add ( 'text_value', ChoiceType::class, [
				'label' => (isset($options['label'])?$options['label']:$fieldType->getName()),
				'required' => false,
				'choices' => $choices,
				'empty_data'  => null,
				'multiple' => false,
				'expanded' => true,
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
		$resolver->setDefault ( 'labels', [] );
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
		] )->add ( 'labels', TextareaType::class, [
				'required' => false,
		] );
	
		// String specific mapping options
		$optionsForm->get ( 'mappingOptions' )->add ( 'analyzer', AnalyzerPickerType::class);
	}
}