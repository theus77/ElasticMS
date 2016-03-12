<?php

namespace AppBundle\Form\View;

use AppBundle\Entity\DataField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
	
	/**
	 * Get a better name than the class path
	 * 
	 * @return string
	 */
	abstract public function getName();

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefault ( 'label', $this->getName().' options');
	}
	
	
}