<?php

namespace AppBundle\Form\DataField;

use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\FieldType\OuuidOptionsType;

class OuuidType extends DataFieldType {
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
	}
	
	public static function isArrayable() {
		return false;
	}	

    /**
     * {@inheritdoc}
     */
    public static function getOptionsFormType(){
    	return OuuidOptionsType::class;
    }
	

}