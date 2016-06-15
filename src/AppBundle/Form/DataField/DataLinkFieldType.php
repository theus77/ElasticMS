<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\ObjectPickerType;
use Elasticsearch\Client;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
												
/**
 * Defined a Container content type.
 * It's used to logically groups subfields together. However a Container is invisible in Elastic search.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
 class DataLinkFieldType extends DataFieldType {

 	/**@var Client $client*/
 	private $client;
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Link to data object(s)';
	}
	
	/**
	 * Get a icon to visually identify a FieldType
	 * 
	 * @return string
	 */
	public static function getIcon(){
		return 'fa fa-sitemap';
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType ()->getDeleted ()) {
			if($data->getFieldType()->getDisplayOptions()['multiple']){
				$out [$data->getFieldType ()->getName ()] = $data->getArrayTextValue();
			}
			else{
				parent::buildObjectArray($data, $out);
			}
				
		}
	}
	
	public function setClient(Client $client){
		$this->client = $client;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {

		/** @var FieldType $fieldType */
		$fieldType = $options ['metadata'];
		
			$builder->add ( $options['multiple']?'array_text_value':'text_value', ObjectPickerType::class, [
					'label' => (null != $options ['label']?$options ['label']:$fieldType->getName()),
					'required' => false,
					'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole()),
					'multiple' => $options['multiple'],
					'type' => $options['type'],
					'dynamicLoading' => $options['dynamicLoading'],
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
		$resolver->setDefault ( 'multiple', false );
		$resolver->setDefault ( 'type', null );
		$resolver->setDefault ( 'environment', null );
		$resolver->setDefault ( 'required', false );
		$resolver->setDefault ( 'dynamicLoading', true );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'options' );
		
		// String specific display options
		$optionsForm->get ( 'displayOptions' )->add ( 'multiple', CheckboxType::class, [ 
				'required' => false,
		] )->add ( 'required', CheckboxType::class, [ 
				'required' => false,
		] )->add ( 'dynamicLoading', CheckboxType::class, [ 
				'required' => false,
		] )->add ( 'type', TextType::class, [ 
				'required' => false,
		] );
		
	}
}