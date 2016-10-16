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
use Symfony\Component\Serializer\Encoder\JsonEncode;

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
		if(!empty($dataField->getRawData())){
			$dateFrom = \DateTime::createFromFormat(\DateTime::ISO8601, $dataField->getRawData()[$dataField->getFieldType()->getName().'_date_from']);
			$dateTo = \DateTime::createFromFormat(\DateTime::ISO8601, $dataField->getRawData()[$dataField->getFieldType()->getName().'_date_to']);

			if($dateFrom && $dateTo){
				$displayformat = DateRangeFieldType::convertJavascriptDateFormat($options['displayOptions']['locale']['format']);
				return $dateFrom->format($displayformat) . ' - ' . $dateTo->format($displayformat);
			}
		}
		return '';
		
	}
	
	/**
	 * {@inheritdoc}
	 *
	 */
	public function setDataValue($input, DataField &$dataField, array $options){
		$format = DateRangeFieldType::convertJavascriptDateFormat($options['displayOptions']['locale']['format']);
		
		$inputs = explode(' - ', $input);
		
		if(count($inputs) == 2){
			$convertedDates = [];
			
			$fromConverted = \DateTime::createFromFormat($format, $inputs[0]);
			if($fromConverted){
				$convertedDates[$dataField->getFieldType()->getName().'_date_from'] = $fromConverted->format(\DateTime::ISO8601);
			}
			
			$toConverted = \DateTime::createFromFormat($format, $inputs[1]);
			if($toConverted){
				$convertedDates[$dataField->getFieldType()->getName().'_date_to'] = $toConverted->format(\DateTime::ISO8601);
			}
						
			$dataField->setRawData($convertedDates);
		}
		else {
			//TODO: log warnign
		}
		
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
		
		$builder->add ( 'data_value', IconTextType::class, [
				'label' => false,
				'required' => false,
				'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole()),
				'icon' => $options['icon'],
				'attr' => [
					'class' => 'ems_daterangepicker',
					'data-display-option' => json_encode($fieldType->getDisplayOptions()),
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
		$out['displayOptions']['timePickerIncrement'] = 5;
		$out['displayOptions']['locale'] = [
				'format' => 'DD/MM/YYYY HH:mm',
				'firstDay' => 1
		];
		
		return $out;
	}
	


	public static function convertJavascriptDateFormat($format){
		$dateFormat = $format;
		//TODO: naive approch....find a way to comvert java date format into php
		$dateFormat = str_replace('DD', 'd', $dateFormat);
		$dateFormat = str_replace('MM', 'm', $dateFormat);
		$dateFormat = str_replace('YYYY', 'Y', $dateFormat);
		$dateFormat = str_replace('hh', 'h', $dateFormat);
		$dateFormat = str_replace('HH', 'H', $dateFormat);
		$dateFormat = str_replace('mm', 'i', $dateFormat);
		$dateFormat = str_replace('ss', 's', $dateFormat);
		$dateFormat = str_replace('aa', 'A', $dateFormat);	
		return $dateFormat;
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
							"format" => 'date_time_no_millis',
						],  array_filter($current->getMappingOptions()))							
					,
					$current->getName()."_date_to" => 
						array_merge([
							"type" => "date",
							"format" => 'date_time_no_millis',
						],  array_filter($current->getMappingOptions()))	
				]
			]
		];
		return $out;
		
		
		

	}
	
	/**
	 * {@inheritdoc}
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType()->getDeleted ()) {
			
// 			$dateFrom = \DateTime::createFromFormat(\DateTime::ISO8601, $data->getRawData()[$data->getFieldType()->getName().'_date_from']);
// 			$dateTo = \DateTime::createFromFormat(\DateTime::ISO8601, $data->getRawData()[$data->getFieldType()->getName().'_date_to']);
			
// 			$format = DateFieldType::convertJavaDateFormat($format);
			
// 			$temp = [];
// 			if($dateFrom){
// 				$temp[$data->getFieldType()->getName().'_date_from'] = $dateFrom->format($format);
// 			}
// 			if($dateTo){
// 				$temp[$data->getFieldType()->getName().'_date_to'] = $dateTo->format($format);
// 			}
			
// 			$out [$data->getFieldType ()->getName ()] = $temp;
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
// 		$optionsForm->get ( 'mappingOptions' )->add ( 'format', TextType::class, [
// 				'required' => false,
// 				'empty_data' => 'yyyy/MM/dd HH:mm',
// 				'attr' => [
// 						'placeholder' => 'i.e. yyyy/MM/dd HH:mm'
// 				],
// 		] );	
		
		$optionsForm->get ( 'displayOptions' )->add ( 'locale', SubOptionsType::class, [
				'required' => false,
				'label' => false,
		] );
		$optionsForm->get ( 'displayOptions' )->get ( 'locale' )->add ( 'format', TextType::class, [
				'required' => false,
				'attr' => [
					'placeholder' => 'i.e. DD/MM/YYYY HH:mm'
				],
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