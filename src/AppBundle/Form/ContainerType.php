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
		foreach ( $fieldType->getChildren() as $key =>  $fieldType ){
			$data = new DataField();
			$data->setFieldType ( $fieldType );
			$data->setParent ( $data );
			$builder->add ( $fieldType->getName(), $fieldType->getType(), [
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
			$classname = $child->getFieldType()->getType();
			$classname::buildObjectArray($child, $out);
		}
	}
}