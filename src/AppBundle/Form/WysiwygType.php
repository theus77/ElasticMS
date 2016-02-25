<?php 

namespace AppBundle\Form;

use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as TextareaSymfonyType;
use Symfony\Component\Form\FormBuilderInterface;

class WysiwygType extends DataFieldType
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
    	
    	$builder->add('text_value', TextareaSymfonyType::class, [
    		'label' => 	$fieldType->getLabel(),
    		'attr' => [
    			'class' => 'ckeditor'
    		]
    	]);  	  			
    	
    }

}