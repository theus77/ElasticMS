<?php 

namespace AppBundle\Form\FieldType;

use AppBundle\Entity\FieldOptions\DataFieldOptions;
use AppBundle\Entity\FieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DataFieldOptionsType extends AbstractType
{
	

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add ( 'displayOptions', DisplayOptionsType::class);
    	if($this->hasMappingOptions()){
	    	$builder->add ( 'mappingOptions', MappingOptionsType::class);    		
    	}
    }   

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
        ));
    }
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'dataFieldOptions';
	}
	
	public function hasMappingOptions() {
		return false;
	}
}