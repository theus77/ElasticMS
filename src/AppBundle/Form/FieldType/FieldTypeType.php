<?php 

namespace AppBundle\Form\FieldType;

use AppBundle\Entity\FieldType;
use AppBundle\Form\DataField\SubfieldType;
use AppBundle\Form\Field\FieldTypePickerType;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

//     	$builder->add ( 'type', HiddenType::class );
    	$builder->add ( 'name', HiddenType::class );
    	
    	
    	$dataFieldType = $fieldType->getTypeClass();
    	$dataFieldType->buildOptionsForm($builder, $options);
    	
    	
    	if($dataFieldType->isContainer()) {
	    	$builder->add ( 'ems:internal:add:field:class', FieldTypePickerType::class, [
	    			'label' => 'Field\'s type',
	    			'mapped' => false,
	    			'required' => false
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

    	}
    	else if(strcmp(SubfieldType::class, $fieldType->getType()) !=0 ) {
    		
	    	$builder->add ( 'ems:internal:add:subfield:name', TextType::class, [
	    			'label' => 'Subfield\'s name',
	    			'mapped' => false,
	    			'required' => false,
	    	]);
	    	
	    	$builder->add ( 'subfield', SubmitEmsType::class, [
	    			'label' => 'Add',
    				'attr' => [
    						'class' => 'btn-primary '
    				],
    				'icon' => 'fa fa-plus'
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

    	if(isset($fieldType) && null != $fieldType->getChildren() && $fieldType->getChildren()->count() > 0){

//     		if($fieldType->getChildren()->count() > 1){
	    		$builder->add ( 'reorder', SubmitEmsType::class, [
	    				'attr' => [
	    						'class' => 'btn-primary '
	    				],
	    				'icon' => 'fa fa-reorder'    			
	    		] );
//     		}
    		
// 	    	$className = $fieldType->getType();
// 	    	/** @var FieldType $instance */
// 	    	$instance = new $className();
	    	
// 			if($instance->isContainer()) {
				/** @var FieldType $field */
				foreach ($fieldType->getChildren() as $idx => $field) {
					if(!$field->getDeleted()){
						$builder->add ( $field->getName(), FieldTypeType::class, [
								'data' => $field,
								'container' => true,
						]  );						
					}
				}
// 			}
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