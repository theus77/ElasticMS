<?php 

namespace AppBundle\Form\FieldType;

use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\IconPickerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Form\Field\AnalyzerPickerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class StringOptionsType extends DataFieldOptionsType
{
	

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	parent::buildForm($builder, $options);
    	$builder->get('displayOptions')
    		->add ( 'icon', IconPickerType::class, [
    			'required' => false,
    		]);
    	$builder->get('mappingOptions')
    		->add ( 'index', ChoiceType::class, [
    			'required' => false,
    			'choices' => [
    				'Analyzed' => 'analyzed',
			        'Not Analyzed' => 'not_analyzed',
    			]
    		])
    		->add ( 'analyzer', AnalyzerPickerType::class)
    		->add ( 'rawSubField', ChoiceType::class, [
    			'required' => false,
    			'choices' => [
			        'No' => false,
			        'Yes' => true,
    			]
    		])
    		->add ( 'boost', NumberType::class, [
    			'required' => false,
    		]);
    }   
    
    public function hasMappingOptions() {
    	return true;
    }
    
    public static function generateMapping(array $options, FieldType $current){
    	$mapping = ['type' => 'string'];
    	if(isset($options['rawSubField']) && $options['rawSubField']){
    		$mapping['fields']['raw']['index'] = 'not_analyzed';
    		$mapping['fields']['raw']['type'] = 'string';
    	}
    
    	if(isset($options['analyzer'])){
    		$mapping['analyzer'] = $options['analyzer'];
    	}
    	 
    	if(isset($options['index'])){
    		$mapping['index'] = $options['index'];
    	}
    	 
    	if(isset($options['boost'])){
    		$mapping['boost'] = $options['boost'];
    	}
    	 
    	return [$current->getName() => $mapping];
    }
}