<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\AnalyzerPickerType;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\IconTextType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\Field\ColorPickerType;
use AppBundle\Form\Field\ColorPickerFullType;
					
/**
 * Defined a Container content type.
 * It's used to logically groups subfields together. However a Container is invisible in Elastic search.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
 class ColorPickerFieldType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Color picker field';
	}
	
	/**
	 * Get a icon to visually identify a FieldType
	 * 
	 * @return string
	 */
	public static function getIcon(){
		return 'fa fa-paint-brush';
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {

		/** @var FieldType $fieldType */
		$fieldType = $options ['metadata'];
		
		$builder->add ( 'text_value', ColorPickerFullType::class, [ 
				'required' => false,
				'label' => (null != $options ['label']?$options ['label']:null)
		] );					
	}
}