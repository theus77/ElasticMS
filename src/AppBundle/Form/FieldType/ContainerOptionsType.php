<?php 

namespace AppBundle\Form\FieldType;

use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\IconPickerType;
use Symfony\Component\Form\FormBuilderInterface;

class ContainerOptionsType extends DataFieldOptionsType
{
	

	/**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	parent::buildForm($builder, $options);
		$builder->get ( 'displayOptions' )->add ( 'icon', IconPickerType::class, [ 
				'required' => false 
		] );
	}
	
	public static function generateMapping(array $options, FieldType $current) {
		$out = [ ];
		
		/** @var FieldType $child */
		foreach ( $current->getChildren () as $child ) {
			if (! $child->getDeleted ()) {
				$child->getTypeClass()->getOptionsFormType();
    				$out = array_merge($out, $child->generateMapping());
    			}
    		}
    		return $out;
        }

}