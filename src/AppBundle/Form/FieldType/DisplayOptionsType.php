<?php 

namespace AppBundle\Form\FieldType;

use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\IconTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class DisplayOptionsType extends AbstractType
{
	

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add ( 'label', IconTextType::class, [
    		'required' => false,
    		'icon' => 'fa fa-tag',
    	]);
    	$builder->add ( 'col-xs', IntegerType::class, [
    		'required' => false,
    		'label' => 'Bootstrap extra small devices',
    	]);
    	$builder->add ( 'col-sm', IntegerType::class, [
    		'required' => false,
    		'label' => 'Bootstrap small devices'
    	]);
    	$builder->add ( 'col-md', IntegerType::class, [
    		'required' => false,
    		'label' => 'Bootstrap medium devices'
    	]);
    	$builder->add ( 'col-lg', IntegerType::class, [
    		'required' => false,
    		'label' => 'Bootstrap large devices'
    	]);
    } 
    
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'displayOptions';
	}
}