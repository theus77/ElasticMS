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
 * Defined a Nested obecjt.
 * It's used to  groups subfields together.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class NestedFieldType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Nested object';
	}	
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function getIcon(){
		return 'glyphicon glyphicon-modal-window';
	}


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function importData(DataField $dataField, $sourceArray, $isMigration){
		$migrationOptions = $dataField->getFieldType()->getMigrationOptions();
		if(!$isMigration || empty($migrationOptions) || !$migrationOptions['protected']) {
			foreach ($dataField->getChildren() as $child){
				$child->updateDataValue($sourceArray);
			}
		}
		return [$dataField->getFieldType()->getName()];
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		/* get the metadata associate */
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions () ['metadata'];
		
		/** @var FieldType $fieldType */
		foreach ( $fieldType->getChildren () as $fieldType ) {

			if (! $fieldType->getDeleted ()) {
				/* merge the default options with the ones specified by the user */
				$options = array_merge ( [ 
						'metadata' => $fieldType,
						'label' => false 
				], $fieldType->getDisplayOptions () );
				$builder->add ( 'ems_' . $fieldType->getName (), $fieldType->getType (), $options );
			}
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
		$view->vars ['multiple'] = $options ['multiple'];
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
		$resolver->setDefault ( 'multiple', false );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if($data->getFieldType () == null){
			$tmp = [];
			/** @var DataField $child */
			foreach ($data->getChildren() as $child){
				$className = $child->getFieldType()->getType();
				$class = new $className;
				$class->buildObjectArray($child, $tmp);
			}
			$out [] = $tmp;
		}
		else if (! $data->getFieldType ()->getDeleted ()) {
			$out [$data->getFieldType ()->getName ()] = [];
		}
	}



	public function isNested(){
		return true;
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
		$optionsForm = $builder->get ( 'options' );
		// nested doesn't not have that much options in elasticsearch
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
	public static function generateMapping(FieldType $current, $withPipeline) {
		return [
			$current->getName() => [
				"type" => "nested",
				"properties" => [],
		]];
	}
}
