<?php 

namespace AppBundle\Form\FieldType;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Form\DataField\CollectionItemFieldType;
use AppBundle\Form\DataField\DataFieldType;
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
	/** @var FieldTypePickerType $fieldTypePickerType */
	private $fieldTypePickerType;
	
	public function __construct(FieldTypePickerType $fieldTypePickerType) {
		$this->fieldTypePickerType = $fieldTypePickerType;
	}
	

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	
    	/** @var FieldType $fieldType */
    	$fieldType = $options['data'];

    	$builder->add ( 'name', HiddenType::class ); 
    	
    	$type = $fieldType->getType();
    	$dataFieldType = new $type;
    	
    	
    	$dataFieldType->buildOptionsForm($builder, $options);
    	
    	
    	if($dataFieldType->isContainer()) {
	    	$builder->add ( 'ems:internal:add:field:class', FieldTypePickerType::class, [
	    			'label' => 'Field\'s type',
	    			'mapped' => false,
	    			'required' => false
	    	]);    	
	    	$builder->add ( 'ems:internal:add:field:name', TextType::class, [
	    			'label' => 'Field\'s machine name',
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


    		$builder->add ( 'reorder', SubmitEmsType::class, [
    				'attr' => [
    						'class' => 'btn-primary '
    				],
    				'icon' => 'fa fa-reorder'    			
    		] );

			/** @var FieldType $field */
			foreach ($fieldType->getChildren() as $idx => $field) {
				if(!$field->getDeleted()){
					$builder->add ( 'ems_'.$field->getName(), FieldTypeType::class, [
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
        	'path' => false,
        	'new_field' => false,
        ));
    }
	
    public function dataFieldToArray(DataField $dataField){
    	$out = [];
    	
    	
    	$dataFieldType = new CollectionItemFieldType();
    	/** @var DataFieldType $dataFieldType */
    	if(null != $dataField->getFieldType()){
	    	$dataFieldType = $this->fieldTypePickerType->getDataFieldType($dataField->getFieldType()->getType());    		
    	}
    	 
    	$dataFieldType->buildObjectArray($dataField, $out);
    	
    	

    	/** @var DataField $child */
    	foreach ( $dataField->getChildren () as $child ) {
    		//its a Collection Item
	    	if ($child->getFieldType() == null){
	    		$subOut = [];
	    		foreach ( $child->getChildren () as $grandchild ) {
	    			$subOut = array_merge($subOut, $this->dataFieldToArray($grandchild));
	    		}
	    		$out[$dataFieldType->getJsonName($dataField->getFieldType())][] = $subOut;
	    	}
	    	else if (! $child->getFieldType()->getDeleted ()) {
	    		if( $dataFieldType->isNested() ){
					$out[$dataFieldType->getJsonName($dataField->getFieldType())] = array_merge($out[$dataFieldType->getJsonName($dataField->getFieldType())], $this->dataFieldToArray($child));
	    		}
// 	    		else if(isset($jsonName)){
// 	    			$out[$jsonName] = array_merge($out[$jsonName], $this->dataFieldToArray($child));
// 	    		}
	    		else{
	    			$out = array_merge($out, $this->dataFieldToArray($child));
	    		}
	    	}
    	}
    	return $out;
    }
    
    public function generateMapping(FieldType $fieldType) {
    	$type = $fieldType->getType();
    	/** @var DataFieldType $dataFieldType */
    	$dataFieldType = new $type();
    	
    	$out = $dataFieldType->generateMapping($fieldType);
    	
    	$jsonName = $dataFieldType->getJsonName($fieldType);
    	/** @var FieldType $child */
    	foreach ( $fieldType->getChildren () as $child ) {
	    	if (! $child->getDeleted ()) {
	    		if(isset($jsonName)){
	    			if(isset($out[$jsonName]["properties"])){
		    			$out[$jsonName]["properties"] = array_merge($out[$jsonName]["properties"], $this->generateMapping($child));
	    			}
	    			else{
		    			$out[$jsonName] = array_merge($out[$jsonName], $this->generateMapping($child));	    				
	    			}
	    		}
	    		else{
		    		$out = array_merge($out, $this->generateMapping($child));	    			
	    		}
	    	}
    	}
    	return $out;
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