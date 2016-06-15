<?php

namespace AppBundle\Form\Form;

use AppBundle\Entity\Revision;
use AppBundle\Form\Field\ColorPickerType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use AppBundle\Form\Field\ObjectPickerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditEnvironmentType extends AbstractType {
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		/** @var Revision $revision */
		$revision = $builder->getData ();
		
		$builder
		->add ( 'name', IconTextType::class, [
			'icon' => 'fa fa-tag'
		] )
		->add ( 'color', ColorPickerType::class, [
				'required' => false,
		])
		->add ( 'circles', ObjectPickerType::class, [
				'required' => false,
				'type' => $options['type'],
				'multiple' => true,
		])
		->add ( 'baseUrl', TextType::class, [
				'required' => false,
		])
		->add ( 'save', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'fa fa-save' 
		] );
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );
		$resolver->setDefault ( 'type', null );
	}
}
