<?php

namespace AppBundle\Form\DataField;




use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Field\AnalyzerPickerType;
use AppBundle\Entity\DataField;

class SubfieldType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Virtual subfield (used to define alternatives analyzers)';
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
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'structuredOptions' );
	
		// String specific mapping options
		$optionsForm->get ( 'mappingOptions' )->add ( 'analyzer', AnalyzerPickerType::class);
	}	

	/**
	 *
	 * {@inheritdoc}
	 *
	 */	
	public static function buildObjectArray(DataField $data, array &$out) {
		//do nothing as it's a virtual field
	}
	
}