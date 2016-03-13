<?php

namespace AppBundle\Form\DataField;




class OuuidFieldType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Copy of the object identifier';
	}	
	
	/**
	 * Get a icon to visually identify a FieldType
	 * 
	 * @return string
	 */
	public static function getIcon(){
		return 'fa fa-key';
	}
	

}