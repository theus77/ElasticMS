<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Form\DataField\Options\OptionsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
abstract class DataFieldType extends AbstractType {
	
	protected $authorizationChecker;
	public function setAuthorizationChecker($authorizationChecker){
		$this->authorizationChecker = $authorizationChecker;
	}

	/**
	 * Used to display in the content type edit page (instaed of the class path)
	 * 
	 * @return string
	 */
	abstract public function getLabel();	


	/**
	 * get the data value(s), as string, for the symfony form) in the context of this field
	 *
	 */
	public function getDataValue(DataField &$dataValues, array $options){
		//TODO: should be abstract ??
		throw new \Exception('This function should never be called');
	}	
	/**
	 * set the data value(s) from a string recieved from the symfony form) in the context of this field
	 *
	 */
	public function setDataValue($input, DataField &$dataValues, array $options){
		//TODO: should be abstract ??
		throw new \Exception('This function should never be called');
	}
	
	/**
	 * Get a icon to visually identify a FieldType
	 * 
	 * @return string
	 */
	public static function getIcon(){
		return 'fa fa-square';
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults ( [ 
				'data_class' => 'AppBundle\Entity\DataField',
				'lastOfRow' => false,
				'class' => null, // used to specify a bootstrap class arround the compoment
				'metadata' => null, // used to keep a link to the FieldType
		]);
	}
	/**
     * Assign data of the dataField based on the elastic index content ($sourceArray)
     * 
	 * @param DataField $dataField
	 * @param unknown $sourceArray
	 */
	public function importData(DataField $dataField, $sourceArray) {
		
		$dataField->setRawData($sourceArray);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		$view->vars ['class'] = $options ['class'];
		$view->vars ['lastOfRow'] = $options ['lastOfRow'];
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

	public function isNested(){
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
		$builder->add ( 'options', OptionsType::class );
	}
	

	public static function getJsonName(FieldType $current){
		return $current->getName();
	}
	
	/**
	 * Build an elasticsearch mapping options as an array
	 * 
	 * @param array $options
	 * @param FieldType $current
	 */
	public static function generateMapping(FieldType $current){
		return [
			$current->getName() => array_merge(["type" => "string"],  array_filter($current->getMappingOptions()))
		];
	}
}