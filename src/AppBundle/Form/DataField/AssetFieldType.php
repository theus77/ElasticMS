<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Field\AssetType;
use AppBundle\Form\Field\IconPickerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
	
/**
 * Defined a Container content type.
 * It's used to logically groups subfields together. However a Container is invisible in Elastic search.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *
 */
class AssetFieldType extends DataFieldType {

	/**
	 * Get a icon to visually identify a FieldType
	 *
	 * @return string
	 */
	public static function getIcon(){
		return 'fa fa-file-o';
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'File field';
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		/** @var FieldType $fieldType */
		$fieldType = $options ['metadata'];
		$builder->add ( 'raw_data', AssetType::class, [
				'label' => (null != $options ['label']?$options ['label']:$fieldType->getName()),
				'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole()),
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
		$optionsForm = $builder->get ( 'options' );
		// container aren't mapped in elasticsearch
		$optionsForm->remove ( 'mappingOptions' );
		// an optional icon can't be specified ritgh to the container label
		$optionsForm->get ( 'displayOptions' )
		->add ( 'icon', IconPickerType::class, [ 
				'required' => false 
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
		$resolver->setDefault ( 'icon', null );
	}
	
	/**
	 * {@inheritdoc}
	 */
	public static function generateMapping(FieldType $current){
		return [
			$current->getName() => array_merge([
					"type" => "nested",
					"properties" => [
							"mimetype" => [
									"type" => "string",
									"index" => "not_analyzed"
							],
							"sha1" => [
									"type" => "string",
									"index" => "not_analyzed"
							],
							"filename" => [
									"type" => "string",
							]
					]
			],  array_filter($current->getMappingOptions()))
		];
	}
}