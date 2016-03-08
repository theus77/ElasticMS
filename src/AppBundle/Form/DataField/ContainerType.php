<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\IconPickerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defined a Container content type.
 * It's used to logically groups subfields together. However a Container is invisible in Elastic search.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class ContainerType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		/* get the metadata assiciate */
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions () ['metadata'];
		
		/** @var FieldType $fieldType */
		foreach ( $fieldType->getChildren () as $fieldType ) {
			/* merge the default options with the ones specified by the user */
			$options = array_merge ( [ 
					'metadata' => $fieldType,
					'label' => false 
			], $fieldType->getDisplayOptions () );
			$builder->add ( 'ems_' . $fieldType->getName (), $fieldType->getType (), $options );
		}
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
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		/** @var DataField $child */
		foreach ( $data->getChildren () as $child ) {
			if (! $child->getFieldType ()->getDeleted ()) {
				/* foreach valid children the subobject array is created */
				$classname = $child->getFieldType ()->getType ();
				$classname::buildObjectArray ( $child, $out );
			}
		}
	}
	
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
		$optionsForm->get ( 'displayOptions' )->add ( 'icon', IconPickerType::class, [ 
				'required' => false 
		] );
	}


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function generateMapping(array $options, FieldType $current) {
		$out = [ ];
	
		/** @var FieldType $child */
		foreach ( $current->getChildren () as $child ) {
			if (! $child->getDeleted ()) {
				$out = array_merge($out, $child->generateMapping());
			}
		}
		return $out;
	}
}
