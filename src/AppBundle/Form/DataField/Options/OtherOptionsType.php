<?php

namespace AppBundle\Form\DataField\Options;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * It's a coumpound field for field specific other option.
 *
 */
class OtherOptionsType extends AbstractType {
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add ( 'other', TextareaType::class, [ 
				'required' => false
		] );
	}
}