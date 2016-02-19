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
				case 'AppBundle\Form\OuuidType':
					$builder->add($fieldType->getName(), OuuidType::class, [
						'metadata' => $fieldType,
						'label' => false,
					]);
					break;
				case 'AppBundle\Form\StringType':
					$builder->add($fieldType->getName(), StringType::class, [
						'metadata' => $fieldType,
						'label' => false,
					]);
					break;
				case 'AppBundle\Form\ContainerType':
					$builder->add($fieldType->getName(), ContainerType::class, [
						'label' => false, //$fieldType->getLabel(),
						'metadata' => $fieldType,
					]);
					break;
				default:
			}
		
		}		
		
		
		//$builder->add('text_value');
	}

}
