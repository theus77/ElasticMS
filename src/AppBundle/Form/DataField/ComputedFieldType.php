<?php

namespace AppBundle\Form\DataField;




use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use AppBundle\Form\Field\AnalyzerPickerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
	 * Get a icon to visually identify a FieldType
	 * 
	 * @return string
	 */
	public static function getIcon(){
		return 'fa fa-gears';
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
		] )->add ( 'json', CheckboxType::class, [ 
				'required' => false,
				'label' => 'Try to JSON decode'
		] )->add ( 'displayTemplate', TextareaType::class, [ 
				'required' => false,
		] );

		$optionsForm->remove('restrictionOptions');
		$optionsForm->remove('migrationOptions');
		$optionsForm->get ('mappingOptions')->add ( 'analyzer', AnalyzerPickerType::class);
		
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