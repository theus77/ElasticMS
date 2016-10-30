<?php

namespace AppBundle\Form\DataField;



use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Field\AnalyzerPickerType;
use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;

//TODO:Refact Class name "SubfieldType" to "SubfieldFieldType"
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
	public function importData(DataField $dataField, $sourceArray, $isMigration) {
		//do nothing as it's a virtual field
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'options' );
	
		// String specific mapping options
		$optionsForm->get ( 'mappingOptions' )->add ( 'analyzer', AnalyzerPickerType::class);
	}	
	
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */	
	public static function generateMapping(FieldType $current, $withPipeline){
		return [
				'fields' => [$current->getName() => array_merge(["type" => "string"],  array_filter($current->getMappingOptions()))]
		];
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