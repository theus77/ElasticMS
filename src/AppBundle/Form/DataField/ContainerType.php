<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\FieldType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Entity\DataField;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use AppBundle\Form\FieldType\ContainerOptionsType;

class ContainerType extends DataFieldType
{
	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		
		/** @var FieldType $fieldType */
		$fieldType = $builder->getOptions()['metadata'];
		
		$options = array_merge([
				'metadata' => $fieldType,
				'label' => $fieldType->getName(),
		], $fieldType->getDisplayOptions());

		/** @var FieldType $fieldType */
		foreach ( $fieldType->getChildren() as $key =>  $fieldType ){
			$data = new DataField();
			$data->setFieldType ( $fieldType );
			$data->setParent ( $data );
			$builder->add ( 'ems_'.$fieldType->getName(), $fieldType->getType(), [
				'metadata' => $fieldType,
				'label' => false,
			]);
		}		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public static function buildObjectArray(DataField $data, array &$out){
		/** @var DataField $child */
		foreach ($data->getChildren() as $child){
			if(! $child->getFieldType()->getDeleted() ){
				$classname = $child->getFieldType()->getType();
				$classname::buildObjectArray($child, $out);				
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
    public static function isContainer() {
    	return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function isArrayable() {
    	return false;
    }    

    /**
     * {@inheritdoc}
     */
    public static function getOptionsFormType(){
    	return ContainerOptionsType::class;
    }
}
