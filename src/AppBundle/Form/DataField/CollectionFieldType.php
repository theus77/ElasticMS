<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Defined a Container content type.
 * It's used to logically groups subfields together. However a Container is invisible in Elastic search.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class CollectionFieldType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Collection (manage array of children types)';
	}	
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function getIcon(){
		return 'fa fa-plus fa-rotate';
	}
	
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		/* get the metadata associate */
		/** @var FieldType $fieldType */
		$fieldType = clone $builder->getOptions () ['metadata'];
		
		$builder->add('ems_' . $fieldType->getName(), CollectionType::class, array(
				// each entry in the array will be an "email" field
				'entry_type' => CollectionItemFieldType::class,
				// these options are passed to each "email" type
				'entry_options' => $options,
				'allow_add' => true,
				'allow_delete' => true,
				'prototype' => true,
				'entry_options' => [
						'metadata' => $fieldType,
				],
		))->add ( 'add_nested', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm add-content-button' 
				],
				'label' => 'Add',
				'icon' => 'fa fa-plus' 
		] );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		/* give options for twig context */
		parent::buildView ( $view, $form, $options );
		$view->vars ['icon'] = $options ['icon'];
		$view->vars ['singularLabel'] = $options ['singularLabel'];
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );
		/* an optional icon can't be specified ritgh to the container label */
		$resolver->setDefault ( 'icon', null );
		$resolver->setDefault ( 'singularLabel', null );
	}
	
// 	/**
// 	 *
// 	 * {@inheritdoc}
// 	 *
// 	 */
// 	public static function buildObjectArray(DataField $data, array &$out) {
		
		
// 	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function isContainer() {
		/* this kind of compound field may contain children */
		return true;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'structuredOptions' );
		// container aren't mapped in elasticsearch
		$optionsForm->remove ( 'mappingOptions' );
		// an optional icon can't be specified ritgh to the container label
		$optionsForm->get ( 'displayOptions' )->add ( 'singularLabel', TextType::class, [ 
				'required' => false 
		] )->add ( 'icon', IconPickerType::class, [ 
				'required' => false 
		] );
	}
	


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType ()->getDeleted ()) {
			$out [$data->getFieldType ()->getName ()] = [];
		}
	}
	
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'collectionfieldtype';
	}


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function getJsonName(FieldType $current){
		return $current->getName();
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function generateMapping(FieldType $current) {
		return [$current->getName () => [
				'type' => 'nested',
				'properties' => []
		]];
	}
}