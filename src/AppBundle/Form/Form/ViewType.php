<?php

namespace AppBundle\Form\Form;

use AppBundle\Entity\Template;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Form\Field\ViewTypePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class ViewType extends AbstractType {
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		/** @var Template $template */
		$template = $builder->getData ();
		
		$builder
		->add ( 'name', IconTextType::class, [
			'icon' => 'fa fa-tag'
		] )
		->add ( 'icon', IconPickerType::class, [
			'required' => false,
		])
		->add ( 'type', ViewTypePickerType::class, [
			'required' => false,
		])
		->add ( 'create', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'fa fa-save' 
		] );
	}
}
