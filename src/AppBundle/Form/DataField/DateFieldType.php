<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\AnalyzerPickerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use AppBundle\Entity\DataValue;

class DateFieldType extends DataFieldType {
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Date field';
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function getIcon(){
		return 'fa fa-calendar';
	}
	
	/**
	 * {@inheritdoc}
	 *
	 */
	public function getDataValue(DataField &$dataField, array $options){
		
		$format = DateFieldType::convertJavaDateFormat($options['displayOptions']['displayFormat']);
		
		$out = [];
		$dates = [];
		/** @var DataValue $dataValue */
		foreach ($dataField->getDataValues() as $dataValue){
			$dates[] = $dataValue->getDateValue()->format($format);
			$out[] = $dataValue->getDateValue();
		}
		return implode(',', $dates);
	}
	
	/**
	 * {@inheritdoc}
	 *
	 */
	public function setDataValue($input, DataField &$dataField, array $options){
		
		$format = DateFieldType::convertJavaDateFormat($options['displayOptions']['displayFormat']);
		if($options['displayOptions']['multidate']){
			$dates = explode(',', $input);
		}
		else{
			$dates = [$input];
		}
		
		$convertedDates = [];
		
		foreach ($dates as $idx => $date){
			$converted = \DateTime::createFromFormat($format, $date);
			if($converted){
				$convertedDates[] = $converted;
			}
		}
		
		$dataField->prepareDataValues(count($convertedDates));
		foreach ($convertedDates as $idx => $date){
			$dataField->getDataValues()->get($idx)->setDateValue($date);							
		}
	}


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'datefieldtype';
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );	
		$resolver->setDefault ( 'displayFormat', 'dd/mm/yyyy' );
		$resolver->setDefault ( 'todayHighlight', false );
		$resolver->setDefault ( 'weekStart', 1 );
		$resolver->setDefault ( 'daysOfWeekHighlighted', '' );
		$resolver->setDefault ( 'daysOfWeekDisabled', '' );
		$resolver->setDefault ( 'multidate', '' );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions () ['metadata'];
	
		$builder->add ( 'data_value', TextType::class, [
				'label' => (isset($options['label'])?$options['label']:$fieldType->getName()),
				'required' => false,
				'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole()),
				'attr' => [
					'class' => 'datepicker',
					'data-date-format' => $fieldType->getDisplayOptions()['displayFormat'],
					'data-today-highlight' => $fieldType->getDisplayOptions()['todayHighlight'],
					'data-week-start' => $fieldType->getDisplayOptions()['weekStart'],
					'data-days-of-week-highlighted' => $fieldType->getDisplayOptions()['daysOfWeekHighlighted'],
					'data-days-of-week-disabled' => $fieldType->getDisplayOptions()['daysOfWeekDisabled'],
					'data-multidate' => $fieldType->getDisplayOptions()['multidate']?"true":"false",
				] 
		] );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function generateMapping(FieldType $current){
		return [
				$current->getName() => array_merge([
						"type" => "date",
				],  array_filter($current->getMappingOptions()))
		];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType()->getDeleted ()) {
			$format = $data->getFieldType()->getMappingOptions()['format'];
			
			$format = DateFieldType::convertJavaDateFormat($format);
			
			if(count($data->getDataValues()) == 1){
				$out [$data->getFieldType ()->getName ()] = $data->getDataValues()->get(0)->getDateValue()->format($format);
			}
			else if (count($data->getDataValues()) > 1){
				$out [$data->getFieldType ()->getName ()] = [];
				/** @var DataValue $date */
				foreach ($data->getDataValues() as $date){
					$out [$data->getFieldType ()->getName ()][] = $date->getDateValue()->format($format);					
				}
				
			}	
		}
	}
	
	public static function convertJavaDateFormat($format){
		$dateFormat = $format;
		//TODO: naive approch....find a way to comvert java date format into php
		$dateFormat = str_replace('dd', 'd', $dateFormat);
		$dateFormat = str_replace('MM', 'm', $dateFormat);
		$dateFormat = str_replace('yyyy', 'Y', $dateFormat);
		$dateFormat = str_replace('hh', 'g', $dateFormat);
		$dateFormat = str_replace('HH', 'G', $dateFormat);
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
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'options' );

		// String specific display options
		$optionsForm->get ( 'mappingOptions' )->add ( 'format', TextType::class, [
				'required' => false,
				'empty_data' => 'yyyy/MM/dd',
				'attr' => [
						'placeholder' => 'i.e. yyyy/MM/dd'
				],
		] );	
		
 		// String specific display options
		$optionsForm->get ( 'displayOptions' )->add ( 'displayFormat', TextType::class, [
				'required' => false,
				'empty_data' => 'dd/mm/yyyy',
				'attr' => [
					'placeholder' => 'i.e. dd/mm/yyyy'
				],
		] );
		$optionsForm->get ( 'displayOptions' )->add ( 'weekStart', IntegerType::class, [
				'required' => false,
				'empty_data' => 0,
				'attr' => [
					'placeholder' => '0'
				],
		] );
		$optionsForm->get ( 'displayOptions' )->add ( 'todayHighlight', CheckboxType::class, [
				'required' => false,
		] );
		$optionsForm->get ( 'displayOptions' )->add ( 'multidate', CheckboxType::class, [
				'required' => false,
		] );
		$optionsForm->get ( 'displayOptions' )->add ( 'daysOfWeekDisabled', TextType::class, [
				'required' => false,
				'attr' => [
					'placeholder' => 'i.e. 0,6'
				],
		] );
		$optionsForm->get ( 'displayOptions' )->add ( 'daysOfWeekHighlighted', TextType::class, [
				'required' => false,
				'attr' => [
					'placeholder' => 'i.e. 0,6'
				],
		] );
	
	}
}