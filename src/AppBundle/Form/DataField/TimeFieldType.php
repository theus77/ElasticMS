<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\AnalyzerPickerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class TimeFieldType extends DataFieldType {
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Time field';
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function getIcon(){
		return 'fa fa-clock-o';
	}
	
	public static function getFormat($options){
		
		if($options['displayOptions']['showMeridian']){
			$format = "g:i";
		}
		else {
			$format = "G:i";
		}
		
		if($options['displayOptions']['showSeconds']){
			$format .= ":s";
		}
		
		if($options['displayOptions']['showMeridian']){
			$format .= " A";
		}
		return $format;
	}
	
	/**
	 * {@inheritdoc}
	 *
	 */
	public function setDataValue($input, DataField &$dataField, array $options){
		$format = $this->getFormat($options);

		$converted = \DateTime::createFromFormat($format, $input);
		if($converted){
			$dataField->prepareDataValues(1);
			$dataField->getDataValues()->get(0)->setDateValue($converted);
		}		
		else {
			$dataField->prepareDataValues(0);
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 */
	public function getDataValue(DataField &$dataField, array $options){
		$format = $this->getFormat($options);
	
		$dates = [];
		/** @var DataValue $dataValue */
		foreach ($dataField->getDataValues() as $dataValue){
			if($dataValue->getDateValue()) {
				$dates[] = $dataValue->getDateValue()->format($format);				
			}
		}
		if(count($dates)){
			$out = implode(',', $dates);			
			return $out;
		}
		return null;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions()['metadata'];
		
		$attr = [
			'class' => 'timepicker',
			'data-show-meridian' => $options['showMeridian']?'true':'false',
// 			'data-provide' => 'timepicker', //for lazy-mode
			'data-default-time'  => $options['defaultTime'],
			'data-show-seconds'  => $options['showSeconds'],
			'data-explicit-mode'  => $options['explicitMode'],
		];
		
		if($options['minuteStep']){
			$attr['data-minute-step'] = $options['minuteStep'];
		}
		
		$builder->add ( 'data_value', TextType::class, [
				'label' => (isset($options['label'])?$options['label']:$fieldType->getName()),
				'required' => false,
				'attr' =>  $attr
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
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'timefieldtype';
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );
		$resolver->setDefault ( 'minuteStep', 15 );
		$resolver->setDefault ( 'showMeridian', false );
		$resolver->setDefault ( 'defaultTime', 'current' );
		$resolver->setDefault ( 'showSeconds', false );
		$resolver->setDefault ( 'explicitMode', true );
	}
	
	/**
	 * {@inheritdoc}
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType()->getDeleted ()) {
			
			$format = $data->getFieldType()->getMappingOptions()['format'];
			
			$format = DateFieldType::convertJavaDateFormat($format);
			
			TimeFieldType::getFormat($data->getFieldType()->getOptions());
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
				'empty_data' => 'HH:mm:ss',
				'attr' => [
					'placeholder' => 'i.e. HH:mm:ss'
				],
		] );

		$optionsForm->get ( 'displayOptions' )->add ( 'minuteStep', IntegerType::class, [
				'required' => false,
				'empty_data' => 15,
		]);
		$optionsForm->get ( 'displayOptions' )->add ( 'showMeridian', CheckboxType::class, [
				'required' => false,
				'label' => 'Show meridian (true: 12hr, false: 24hr)'
		]);
		$optionsForm->get ( 'displayOptions' )->add ( 'defaultTime', TextType::class, [
				'required' => false,
 				'label' => 'Default time (empty: current time, \'11:23\': specific time, \'false\': do not set a default time)'
		]);
		$optionsForm->get ( 'displayOptions' )->add ( 'showSeconds', CheckboxType::class, [
				'required' => false,
		]);
		$optionsForm->get ( 'displayOptions' )->add ( 'explicitMode', CheckboxType::class, [
				'required' => false,
		]);
	
	}
}