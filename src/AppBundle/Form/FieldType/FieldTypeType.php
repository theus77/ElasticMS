<?php 

namespace AppBundle\Form\FieldType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormView;
use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FieldTypeType extends AbstractType
{
	

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add ( 'label', TextType::class, [
    		'required' => false,
    	] );
    	$builder->add ( 'many' );
//     	$builder->add ( 'name' );
//     	$builder->add ( 'type' );
//     	$builder->add ( 'orderKey' );

    	if(isset($options['data']) && null != $options['data']->getChildren()){
    		
	    	$className = $options['data']->getType();
	    	/** @var FieldType $instance */
	    	$instance = new $className();
	    	
			if($instance->isContainer()) {
				/** @var FieldType $field */
				foreach ($options['data']->getChildren() as $idx => $field) {
					$builder->add ( $field->getName(), FieldTypeType::class, [
							'data' => $field,
							'container' => true,
					]  );
				}
			}
    	}
    }   

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\FieldType',
        	'container' => false,
        ));
    }
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'fieldTypeType';
	}
}