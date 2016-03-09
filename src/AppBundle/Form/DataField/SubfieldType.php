<?php

namespace AppBundle\Form\DataField;




use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Field\AnalyzerPickerType;

class SubfieldType extends DataFieldType {
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'structuredOptions' );

	
		// String specific mapping options
		$optionsForm->get ( 'mappingOptions' )->add ( 'analyzer', AnalyzerPickerType::class);
	}
	
}