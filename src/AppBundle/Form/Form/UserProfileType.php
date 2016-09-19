<?php

namespace AppBundle\Form\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserProfileType extends AbstractType {
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {

		$builder->add ( 'displayName');
		$builder->remove('username');
	}
	

	public function getParent()
	{
		return 'FOS\UserBundle\Form\Type\ProfileFormType';
	}
	
}
