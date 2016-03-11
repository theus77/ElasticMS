<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\AnalyzerPickerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class CheckboxFieldType extends DataFieldType {

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Checkbox field';
	}
	
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
	
		$builder->add ( 'boolean_value', CheckboxType::class, [
				'label' => (isset($options['label'])?$options['label']:$fieldType->getName()),
				'required' => false,
		] );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'structuredOptions' );
	
// 		// String specific display options
// 		$optionsForm->get ( 'displayOptions' )->add ( 'choices', TextareaType::class, [
// 				'required' => false,
// 		] )->add ( 'labels', TextareaType::class, [
// 				'required' => false,
// 		] );
	
// 		// String specific mapping options
// 		$optionsForm->get ( 'mappingOptions' )->add ( 'analyzer', AnalyzerPickerType::class);
	}
}