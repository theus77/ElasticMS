<?php 

namespace AppBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class StringType extends DataFieldType
{
	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {  
    	$builder->add('text_value');
    	
    }

}