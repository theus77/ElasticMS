<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\DataField\Options\OptionsType;
use AppBundle\Entity\FieldType;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
abstract class DataFieldType extends AbstractType {

	/**
	 * Used to display in the content type edit page (instaed of the class path)
	 */
	abstract public function getLabel();
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults ( [ 
				'data_class' => 'AppBundle\Entity\DataField',
				'class' => null, // used to specify a bootstrap class arround the compoment
				'metadata' => null, // used to keep a link to the FieldType
		] 
 );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		$view->vars ['class'] = $options ['class'];
		$view->vars ['isContainer'] = $this->isContainer();
		if( null == $options['label']){
			/** @var FieldType $fieldType */
			$fieldType = $options ['metadata'];
			$view->vars ['label'] = $fieldType->getName();
		}
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'datafieldtype';
	}
	
	/**
	 * Build an array representing the object, this array is ready to be serialized in json
	 * and push in elasticsearch
	 *
	 * @return array
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType ()->getDeleted ()) {
			/**
			 * by default it serialize the text value.
			 * It can be overrided.
			 */
			$out [$data->getFieldType ()->getName ()] = $data->getTextValue ();
		}
	}
	
	/**
	 * Test if the field may contain sub field.
	 *
	 * I.e. container, nested, array, ...
	 *
	 * @return boolean
	 */
	public static function isContainer() {
		return false;
	}
	
	/**
	 * Build a Field specific options sub-form (or compount field) (used in edit content type).
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		/**
		 * preset with the most used options
		 */
		$builder->add ( 'structuredOptions', OptionsType::class );
	}
	
	/**
	 * Build an elasticsearch mapping options as an array
	 * 
	 * @param array $options
	 * @param FieldType $current
	 */
	public static function generateMapping(array $options, FieldType $current){
		$out = [
			$current->getName() => array_merge(
					array_filter($options), 
					['type' => 'string'])
		];
		
		/* has subfields ?*/
		if( $current->getChildren()->count() > 0){
			$fields = [];
			/** @var FieldType $child */
			foreach ( $current->getChildren () as $child ) {
				if (! $child->getDeleted ()) {
					$fields = array_merge($fields, $child->generateMapping());
				}
			}
			$out[$current->getName()]['fields'] = $fields;
		}
		
		return $out;
		
		
		/* Elasticsearch doesnt accept parameter with null value, that the goals of the array_filter call */
		return [];
	}
}