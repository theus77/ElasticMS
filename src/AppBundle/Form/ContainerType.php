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
			$data->setFieldType($fieldType);
			$data->setParent($data);
			
			switch ($fieldType->getType()){
				case 'ouuid':
					$builder->add($fieldType->getName(), OuuidType::class, [
						'metadata' => $fieldType,
					]);
					break;
				case 'string':
					$builder->add($fieldType->getName(), StringType::class, [
						'metadata' => $fieldType,
					]);
					break;
				case 'container':
					$builder->add($fieldType->getName(), ContainerType::class, [
						'label' => $fieldType->getLabel(),
						'metadata' => $fieldType
					]);
					break;
				default:
			}
		
		}		
		
		
		//$builder->add('text_value');
	}

}
