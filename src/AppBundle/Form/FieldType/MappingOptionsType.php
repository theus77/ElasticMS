<?php 

namespace AppBundle\Form\FieldType;

use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\IconTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class MappingOptionsType extends AbstractType
{
	

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	
    } 
    
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'mappingOptions';
	}
}