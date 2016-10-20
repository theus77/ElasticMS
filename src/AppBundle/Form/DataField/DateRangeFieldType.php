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
			$temp = $dataField->getRawData();
			
			$dateFrom = \DateTime::createFromFormat(\DateTime::ISO8601, $temp[$options['mappingOptions']['fromDateMachineName']]);
			$dateTo = \DateTime::createFromFormat(\DateTime::ISO8601, $temp[$options['mappingOptions']['toDateMachineName']]);

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
				$convertedDates[$options['mappingOptions']['fromDateMachineName']] = $fromConverted->format(\DateTime::ISO8601);
			}
			
			$toConverted = \DateTime::createFromFormat($format, $inputs[1]);
			if($toConverted){
				$convertedDates[$options['mappingOptions']['toDateMachineName']] = $toConverted->format(\DateTime::ISO8601);
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
	public function isVirtualField(array $option){
		return !$option['mappingOptions']['nested'];
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function importData(DataField $dataField, $sourceArray, $isMigration) {
		$migrationOptions = $dataField->getFieldType()->getMigrationOptions();
		if(!$isMigration || empty($migrationOptions) || !$migrationOptions['protected']) {
			
			if(!$dataField->getFieldType()->getMappingOptions()['nested']){
				$out = [];
				$in = [];
				if(isset($sourceArray[$dataField->getFieldType()->getMappingOptions()['fromDateMachineName']])){
					$out[] = $dataField->getFieldType()->getMappingOptions()['fromDateMachineName'];
					$in[$dataField->getFieldType()->getMappingOptions()['fromDateMachineName']] = $sourceArray[$dataField->getFieldType()->getMappingOptions()['fromDateMachineName']];
				}
				if(isset($sourceArray[$dataField->getFieldType()->getMappingOptions()['toDateMachineName']])){
					$out[] = $dataField->getFieldType()->getMappingOptions()['toDateMachineName'];
					$in[$dataField->getFieldType()->getMappingOptions()['toDateMachineName']] = $sourceArray[$dataField->getFieldType()->getMappingOptions()['toDateMachineName']];
				}
				$dataField->setRawData($in);
				return $out;
			}
			else{
				return parent::importData($dataField, $sourceArray, $isMigration);
			}
		}
		return [$dataField->getFieldType()->getName()];
	}
	
	
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
				'label' => (null != $options ['label']?$options ['label']:$fieldType->getName()),
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
		
		$out['mappingOptions']['toDateMachineName'] = $name.'_to_date';
		$out['mappingOptions']['fromDateMachineName'] = $name.'_from_date';
		$out['mappingOptions']['nested'] = true;
		$out['mappingOptions']['index'] = null;
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
			$current->getMappingOptions()['fromDateMachineName'] => 
			[
				"type" => "date",
				"format" => 'date_time_no_millis',
			],
			$current->getMappingOptions()['toDateMachineName'] => 
			[
				"type" => "date",
				"format" => 'date_time_no_millis',
			],
		];
		
		if(!empty($current->getMappingOptions()['index'])) {
			$current->getMappingOptions()['fromDateMachineName']['index'] = $current->getMappingOptions()['index'];
			$current->getMappingOptions()['toDateMachineName']['index'] = $current->getMappingOptions()['index'];
		}
		
		if($current->getMappingOptions()['nested']){
			$out = [
				$current->getName() => [
					"type" => "nested",
					"properties" => $out
				]
			];			
		}
		
		return $out;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType()->getDeleted ()) {
			if($data->getFieldType()->getMappingOptions()['nested']){
				$out [$data->getFieldType ()->getName ()] = $data->getRawData();				
			}
			else {
				$rawData = $data->getRawData();
				if(empty($rawData)){
					$rawData = [];
				}
				
				$out = array_merge($out, $rawData);
			}
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
		$optionsForm->get ( 'mappingOptions' )->add ( 'fromDateMachineName', TextType::class, [
				'required' => false,
		] )->add ( 'toDateMachineName', TextType::class, [
				'required' => false,
		] )->add ( 'nested', CheckboxType::class, [
				'required' => false,
		] );	
		
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