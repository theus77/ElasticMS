<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\DataValue;
use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\DataField\Options\SubOptionsType;

class DateRangeFieldType extends DataFieldType {
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Date range field';
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function getIcon(){
		return 'fa fa-calendar-o';
	}
	
	/**
	 * {@inheritdoc}
	 *
	 */
	public function getDataValue(DataField &$dataField, array $options){
		return '';
// 		$format = DateFieldType::convertJavaDateFormat($options['displayOptions']['displayFormat']);

// 		$dates = [];
// 		if(null !== $dataField->getRawData()){
// 			foreach ($dataField->getRawData() as $dataValue){
// 				/**@var \DateTime $converted*/
// 				$dateTime = \DateTime::createFromFormat(\DateTime::ISO8601, $dataValue);
// 				if($dateTime){
// 					$dates[] = $dateTime->format($format);
// 				}
// 				else{
// 					$dates[] = null;
// 					//TODO: should add a flash message
// 				}
// 			}			
// 		}
// 		return implode(',', $dates);
		
	}
	
	/**
	 * {@inheritdoc}
	 *
	 */
	public function setDataValue($input, DataField &$dataField, array $options){
		//Do nothing it's just a display field
// 		$format = DateFieldType::convertJavaDateFormat($options['displayOptions']['displayFormat']);
// 		if($options['displayOptions']['multidate']){
// 			$dates = explode(',', $input);
// 		}
// 		else{
// 			$dates = [$input];
// 		}
		
// 		$convertedDates = [];
		
// 		foreach ($dates as $idx => $date){
// 			/**@var \DateTime $converted*/
// 			$converted = \DateTime::createFromFormat($format, $date);
// 			if($converted){
// 				$convertedDates[] = $converted->format(\DateTime::ISO8601);
// 			}
// 		}
		
// 		$dataField->setRawData($convertedDates);
	}


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'daterangefieldtype';
	}
	
	
// 	/**
// 	 *
// 	 * {@inheritdoc}
// 	 *
// 	 */
// 	public function importData(DataField $dataField, $sourceArray, $isMigration) {
// 		$migrationOptions = $dataField->getFieldType()->getMigrationOptions();
// 		if(!$isMigration || empty($migrationOptions) || !$migrationOptions['protected']) {
// 			$format = $dataField->getFieldType()->getMappingOptions()['format'];	
// 			$format = DateFieldType::convertJavaDateFormat($format);
		
// 			if(null == $sourceArray) {
// 				$sourceArray = [];
// 			}
// 			if(is_string($sourceArray)){
// 				$sourceArray = [$sourceArray];
// 			}
// 			$data = [];
// // 			dump($sourceArray);
// 			foreach ($sourceArray as $idx => $child){
// 				$dateObject = \DateTime::createFromFormat($format, $child);
// 				$data[] = $dateObject->format(\DateTime::ISO8601);
// 			}
// 			$dataField->setRawData($data);
// 		}
// 	}
	
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );	
		$resolver->setDefault ( 'showWeekNumbers', false );
		$resolver->setDefault ( 'timePicker', true );
		$resolver->setDefault ( 'timePicker24Hour', true );
		$resolver->setDefault ( 'timePickerIncrement', 5 );
		$resolver->setDefault ( 'icon', null );
		$resolver->setDefault ( 'locale', [] );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions () ['metadata'];
	
		$builder->add ( 'raw_data', HiddenType::class, [
				'label' => null,
				'required' => false,
				'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole()),
// 				'attr' => [
// 					'class' => 'datepicker',
// 					'data-date-format' => $fieldType->getDisplayOptions()['displayFormat'],
// 					'data-today-highlight' => $fieldType->getDisplayOptions()['todayHighlight'],
// 					'data-week-start' => $fieldType->getDisplayOptions()['weekStart'],
// 					'data-days-of-week-highlighted' => $fieldType->getDisplayOptions()['daysOfWeekHighlighted'],
// 					'data-days-of-week-disabled' => $fieldType->getDisplayOptions()['daysOfWeekDisabled'],
// 					'data-multidate' => $fieldType->getDisplayOptions()['multidate']?"true":"false",
// 				] 
		] );		
		
		$builder->add ( 'data_value', IconTextType::class, [
				'label' => false,
				'required' => false,
				'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole()),
				'icon' => $options['icon'],
				'attr' => [
					'class' => 'daterangepicker',
// 					'data-date-format' => $fieldType->getDisplayOptions()['displayFormat'],
// 					'data-today-highlight' => $fieldType->getDisplayOptions()['todayHighlight'],
// 					'data-week-start' => $fieldType->getDisplayOptions()['weekStart'],
// 					'data-days-of-week-highlighted' => $fieldType->getDisplayOptions()['daysOfWeekHighlighted'],
// 					'data-days-of-week-disabled' => $fieldType->getDisplayOptions()['daysOfWeekDisabled'],
// 					'data-multidate' => $fieldType->getDisplayOptions()['multidate']?"true":"false",
				] 
		] );
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getDefaultOptions($name) {
		$out = parent::getDefaultOptions($name);
		$out['mappingOptions']['format'] = 'yyyy/MM/dd HH:mm';
		$out['displayOptions']['timePickerIncrement'] = 5;
		$out['displayOptions']['locale'] = [
				'format' => 'MM/DD/YYYY HH:MM',
				'firstDay' => 1
		];
		
		return $out;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function generateMapping(FieldType $current){
		$out = [
			$current->getName() => [
				"type" => "nested",
				"properties" =>[
					$current->getName()."_date_from" => 
						array_merge([
							"type" => "date",
						],  array_filter($current->getMappingOptions()))							
					,
					$current->getName()."_date_to" => 
						array_merge([
							"type" => "date",
						],  array_filter($current->getMappingOptions()))	
				]
			]
		];
		
		dump($out);
			
		return $out;
		
		
		

	}
	
	/**
	 * {@inheritdoc}
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType()->getDeleted ()) {
			$format = $data->getFieldType()->getMappingOptions()['format'];
			
			$format = DateFieldType::convertJavaDateFormat($format);
			
			$dates = [];
			if(null !== $data->getRawData()['ems_date_from']){
				/**@var \DateTime $converted*/
				$dateTime = \DateTime::createFromFormat(\DateTime::ISO8601, $data->getRawData()['ems_date_from']);
				$dates['ems_date_from'] = $dateTime->format($format);
			}	
			if(null !== $data->getRawData()['ems_date_to']){
				/**@var \DateTime $converted*/
				$dateTime = \DateTime::createFromFormat(\DateTime::ISO8601, $data->getRawData()['ems_date_to']);
				$dates['ems_date_to'] = $dateTime->format($format);
			}	
			
			$out [$data->getFieldType ()->getName ()] = $dates;
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
		$optionsForm->get ( 'mappingOptions' )->add ( 'format', TextType::class, [
				'required' => false,
				'empty_data' => 'yyyy/MM/dd HH:mm',
				'attr' => [
						'placeholder' => 'i.e. yyyy/MM/dd HH:mm'
				],
		] );	
		
		$optionsForm->get ( 'displayOptions' )->add ( 'locale', SubOptionsType::class, [
				'required' => false,
				'label' => false,
		] );
		$optionsForm->get ( 'displayOptions' )->get ( 'locale' )->add ( 'format', TextType::class, [
				'required' => false
		] );
		$optionsForm->get ( 'displayOptions' )->get ( 'locale' )->add ( 'firstDay', IntegerType::class, [
				'required' => false,
		] );
		$optionsForm->get ( 'displayOptions' )->add ( 'icon', IconPickerType::class, [ 
				'required' => false 
		] );
		$optionsForm->get ( 'displayOptions' )->add ( 'showWeekNumbers', CheckboxType::class, [
				'required' => false,
		] );
		$optionsForm->get ( 'displayOptions' )->add ( 'timePicker', CheckboxType::class, [
				'required' => false,
		] );
		$optionsForm->get ( 'displayOptions' )->add ( 'timePicker24Hour', CheckboxType::class, [
				'required' => false,
		] );
		
		$optionsForm->get ( 'displayOptions' )->add ( 'timePickerIncrement', IntegerType::class, [
				'required' => false,
				'empty_data' => 5,
				'attr' => [
					'placeholder' => '5'
				],
		] );
	
	}
}