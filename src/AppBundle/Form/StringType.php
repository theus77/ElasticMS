<?php 

namespace AppBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

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