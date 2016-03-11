<?php

namespace AppBundle\Form\View;

use AppBundle\Entity\DataField;
use Symfony\Component\Form\AbstractType;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class KeywordsViewType extends AbstractType {

	/**
	 * Get a small description
	 * 
	 * @return string
	 */
	public function getLabel(){
		return "Keywords";
	}
	
}