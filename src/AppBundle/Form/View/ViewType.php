<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use Symfony\Component\Form\AbstractType;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
abstract class ViewType extends AbstractType {

	/**
	 * Get a small description
	 * 
	 * @return string
	 */
	abstract public function getLabel();
	
}