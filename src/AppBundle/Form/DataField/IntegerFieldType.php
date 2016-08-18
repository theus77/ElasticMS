<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\AnalyzerPickerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class IntegerFieldType extends DataFieldType {

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Integer field';
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function getIcon(){
		return 'glyphicon glyphicon-sort-by-order';
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function importData(DataField $dataField, $sourceArray, $isMigration) {
		$migrationOptions = $dataField->getFieldType()->getMigrationOptions();
		if(!$isMigration || empty($migrationOptions) || !$migrationOptions['protected']) {
			$dataField->setIntegerValue($sourceArray);
		}
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions () ['metadata'];
	
		$builder->add ( 'integer_value', TextType::class, [
				'label' => (isset($options['label'])?$options['label']:$fieldType->getName()),
				'required' => false,
				'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole()),
				'attr' => [
						//'class' => 'spinner',
				]
		] );
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function isValid(DataField &$dataField){
		$isValid = parent::isValid ( $dataField );

		if($dataField->getRawData() !== null && (!intval($dataField->getRawData()) || $dataField->getRawData() === '0' || $dataField->getRawData() === 0 )){
			$dataField->addMessage("Misformated integer ".$dataField->getRawData());
			return FALSE;
		}
		
		return $isValid;
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
			 * It must be overrided.
			 */
			$out [$data->getFieldType ()->getName ()] = $data->getIntegerValue();
		}
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function generateMapping(FieldType $current){
		return [
				$current->getName() => array_merge(["type" => "integer"],  array_filter($current->getMappingOptions()))
		];
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'options' );
		
	
// 		// String specific display options
// 		$optionsForm->get ( 'displayOptions' )->add ( 'choices', TextareaType::class, [
// 				'required' => false,
// 		] )->add ( 'labels', TextareaType::class, [
// 				'required' => false,
// 		] );
	
// 		// String specific mapping options
// 		$optionsForm->get ( 'mappingOptions' )->add ( 'analyzer', AnalyzerPickerType::class);
	}
}