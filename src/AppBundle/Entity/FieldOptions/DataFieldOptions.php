<?php
namespace AppBundle\Entity\FieldOptions;


/**
 * RebuildIndex
 */
class DataFieldOptions
{
	private $icon;
	
	public function setIcon($icon)
	{
		$this->icon = $icon;
	
		return $this;
	}
	
	public function getIcon()
	{
		return $this->icon;
	}
	
	private $label;
	
	public function setLabel($icon)
	{
		$this->label = $label;
	
		return $this;
	}
	
	public function getLabel()
	{
		return $this->label;
	}
}