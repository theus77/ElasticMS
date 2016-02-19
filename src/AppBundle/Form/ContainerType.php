<?php

namespace AppBundle\Form;

use AppBundle\Entity\FieldType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Entity\DataField;

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
		$data = $builder->getData();
		
		/** @var FieldType $fieldType */
		foreach ( $fieldType->getChildren() as $fieldType ){
			$data = new DataField();
			$data->setFieldType($fieldType);
			$data->setParent($data);
			
			$builder->add($fieldType->getName(), $fieldType->getType(), [
					'metadata' => $fieldType,
					'label' => false,
					'data' => $data
			]);
		
		}		
		
	}
	
	public function buildObjectArray(array &$out, DataField $data){
		/** @var DataField $data */
		foreach ( $data->getChildren() as $child ){
			$class = $child->getFieldType()->getType();
			/** @var DataFieldType $dataFieldType */
			$dataFieldType = new $class();
			$dataFieldType->buildObjectArray($out, $child);
		}
	}
}
