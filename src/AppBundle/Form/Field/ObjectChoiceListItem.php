<?php 
namespace AppBundle\Form\Field;

use AppBundle\Entity\ContentType;

class ObjectChoiceListItem {

	private $object;
	
	/**@var ContentType $contentType*/
	private $contentType;
	
	public function __construct(array $object){
		$this->object = $object;
		$this->contentType = NULL;
	}
	
	public function getObject(){
		return $this->object;
	}

	public function getContentType(){
		return $this->contentType;
	}
	
	public function setContentType(ContentType $contentType){
		$this->contentType = $contentType;
		return $this;
	}
	
	public function getKey(){
		return $this->object['_type'].':'.$this->object['_id'];
	}
	
	public function getLabel($key){
		$out = '<i class="fa fa-question"></i> '.$key;
		if( null !== $this->contentType ) {
			$out = '<i class="'.(null !== $this->contentType->getIcon()?$this->contentType->getIcon():'fa fa-question').'"></i> ';
			if(null !== $this->contentType->getLabelField() && isset($this->object['_source'][$this->contentType->getLabelField()])){
				$out .= $this->object['_source'][$this->contentType->getLabelField()];
			}
// 			else {
				$out .= ' ('.$key. ')';
// 			}
		}
		 
		return $out;
	}
	

	public function __toString()
	{
		return $this->getKey();
	}
}