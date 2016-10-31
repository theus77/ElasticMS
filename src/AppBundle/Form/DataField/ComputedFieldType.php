<?php

namespace AppBundle\Form\DataField;




use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\FieldType;
use AppBundle\Entity\DataField;

class ComputedFieldType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Computed from the raw-data';
	}	
	


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function generateMapping(FieldType $current, $withPipeline){
		if(!empty($current->getMappingOptions()) && !empty($current->getMappingOptions()['mappingOptions'])){
			return [ $current->getName() =>  $current->getMappingOptions()['mappingOptions']];
		}
		return [];
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function getIcon(){
		return 'fa fa-gears';
	}


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType ()->getDeleted ()) {
			/**
			 * by default it serialize the text value.
			 * It can be overrided.
			 */
			$out [$data->getFieldType ()->getName ()] = $data->getRawData();
		}
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
		$optionsForm->get ( 'displayOptions' )->add ( 'valueTemplate', TextareaType::class, [ 
				'required' => false,
				'attr' => [
					'rows' => 8,
				],
		] )->add ( 'json', CheckboxType::class, [ 
				'required' => false,
				'label' => 'Try to JSON decode'
		] )->add ( 'displayTemplate', TextareaType::class, [ 
				'required' => false,
				'attr' => [
					'rows' => 8,
				],
		] );


		$optionsForm->get ( 'mappingOptions' )->remove('index')->remove('analyzer')->add('mappingOptions', TextareaType::class, [ 
				'required' => false,
				'attr' => [
					'rows' => 8,
				],
		] );
		$optionsForm->remove('restrictionOptions');
		$optionsForm->remove('migrationOptions');
		
	}


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );
		$resolver->setDefault ( 'displayTemplate', NULL );
		$resolver->setDefault ( 'json', false );
		$resolver->setDefault ( 'valueTemplate', NULL );
	}
	

}