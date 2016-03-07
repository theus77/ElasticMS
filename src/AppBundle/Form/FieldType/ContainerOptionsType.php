<?php 

namespace AppBundle\Form\FieldType;

use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\IconPickerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ContainerOptionsType extends DataFieldOptionsType
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
    }   
	

}