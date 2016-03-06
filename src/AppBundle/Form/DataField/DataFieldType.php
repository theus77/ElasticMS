<?php 

namespace AppBundle\Form\DataField;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\DataField;
use AppBundle\Form\FieldType\DataFieldOptionsType;

class DataFieldType extends AbstractType
{
	

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

    }   

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\DataField',
        	'metadata' => null,
        	'col-xs' => null,
        	'col-sm' => null,
        	'col-md' => null,
        	'col-lg' => null,
        ));
    }
    
    public static function buildObjectArray(DataField $data, array &$out){
    	if(!$data->getFieldType()->getDeleted()){
	    	$out [$data->getFieldType()->getName()] = $data->getTextValue();    	    		
    	}
    }
    
    public static function isContainer() {
    	return false;
    }
    
    public static function getOptionsFormType(){
    	return DataFieldOptionsType::class;
    }
    
}