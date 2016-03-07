<?php 

namespace AppBundle\Form\FieldType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormView;
use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use AppBundle\Form\Field\SubmitEmsType;

class FieldTypeType extends AbstractType
{
	

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	
    	/** @var FieldType $fieldType */
    	$fieldType = $options['data'];

    	$builder->add ( 'type', HiddenType::class );
    	$builder->add ( 'name', HiddenType::class );
    	
    	if(!$fieldType->getTypeClass()->isContainer()){
	    	$builder->add ( 'many' );    		
    	}
    	$builder->add ( 'structuredOptions', $fieldType->getOptionsFormType());
    	
    	$currentType = $fieldType->getType();
    	$currentTypeCLass = new $currentType;
    	
    	
    	if($currentTypeCLass->isContainer()) {
	    	$builder->add ( 'ems:internal:add:field:class', ChoiceType::class, [
	    			'label' => 'Field\'s type',
	    			'mapped' => false,
	    			'required' => false,
	    			'choices' => [
	    				'Container' => 'AppBundle\Form\DataField\ContainerType',
	    				'Ouuid' => 'AppBundle\Form\DataField\OuuidType',
	    				'String' => 'AppBundle\Form\DataField\StringType',
	    				'WYSIWYG' => 'AppBundle\Form\DataField\WysiwygType',
	    				'TextArea' => 'AppBundle\Form\DataField\TextAreaType',
	    			]
	    	]);    	
	    	$builder->add ( 'ems:internal:add:field:name', TextType::class, [
	    			'label' => 'Field\'s name',
	    			'mapped' => false,
	    			'required' => false,
	    	]);

	    	$builder->add ( 'add', SubmitEmsType::class, [
	    			'attr' => [
	    					'class' => 'btn-primary '
	    			],
	    			'icon' => 'fa fa-plus'
	    	] );

	    	$builder->add ( 'reorder', SubmitEmsType::class, [
	    			'attr' => [
	    					'class' => 'btn-primary '
	    			],
	    			'icon' => 'fa fa-reorder'
	    	] );
    	}    	
    	if(null != $fieldType->getParent()){
	    	$builder->add ( 'remove', SubmitEmsType::class, [
	    			'attr' => [
	    					'class' => 'btn-danger btn-xs'
	    			],
	    			'icon' => 'fa fa-trash'
	    	] );	    		
    	}

    	if(isset($fieldType) && null != $fieldType->getChildren()){
    		
	    	$className = $fieldType->getType();
	    	/** @var FieldType $instance */
	    	$instance = new $className();
	    	
			if($instance->isContainer()) {
				/** @var FieldType $field */
				foreach ($fieldType->getChildren() as $idx => $field) {
					if(!$field->getDeleted()){
						$builder->add ( $field->getName(), FieldTypeType::class, [
								'data' => $field,
								'container' => true,
						]  );						
					}
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
        	'path' => false,
        	'new_field' => false,
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