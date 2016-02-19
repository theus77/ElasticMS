<?php 

namespace AppBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Entity\FieldType;

class StringType extends DataFieldType
{
	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {  
    	/** @var FieldType $fieldType */
    	$fieldType = $builder->getOptions()['metadata'];
    	$data = $builder->getData();
    	
    	if($fieldType->getIcon()){
    		$builder->add('text_value', IconTextType::class, [
    				'label' => 	$fieldType->getLabel(),
    				'icon' => $fieldType->getIcon()
    		]);    		
    	}
    	else {
	    	$builder->add('text_value', TextType::class, [
	    		'label' => 	$fieldType->getLabel()
	    	]);    		
    	}
    	
    }

}