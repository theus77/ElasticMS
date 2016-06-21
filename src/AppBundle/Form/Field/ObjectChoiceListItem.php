<?php 
namespace AppBundle\Form\Field;

use AppBundle\Entity\ContentType;

class ObjectChoiceListItem {

	private $label;
	private $value;
	private $group;
	
	
	public function __construct(array &$object, ContentType $contentType){
		$this->value = $object['_type'].':'.$object['_id'];
		
		
		$this->group = null;
		if( null !== $contentType && $contentType->getCategoryField() && isset($object['_source'][$contentType->getCategoryField()] )) {
			$this->group = $object['_source'][$contentType->getCategoryField()];
		}
		
		$this->label = '<i class="fa fa-question"></i> '.$this->value;
		if( null !== $contentType ) {
			$this->label = '<i class="'.(null !== $contentType->getIcon()?$contentType->getIcon():'fa fa-question').'"></i> ';
			if(null !== $contentType->getLabelField() && isset($object['_source'][$contentType->getLabelField()])){
				$this->label .= $object['_source'][$contentType->getLabelField()];
			}
			else {
				$this->label .= $this->value;				
			}
		}
	}	
	
	
	
	public function getValue(){
		return $this->value;
	}
	
	public function getLabel(){
		return $this->label;
	}	
	
	public function getGroup(){
		return $this->group;
	}	

	public function __toString()
	{
		return $this->getValue();
	}
}