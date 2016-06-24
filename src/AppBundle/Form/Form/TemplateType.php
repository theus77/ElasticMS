<?php

namespace AppBundle\Form\Form;

use AppBundle\Entity\Template;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\RenderOptionType;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Field\RolePickerType;
use AppBundle\Form\Field\ObjectPickerType;
use Symfony\Component\OptionsResolver\OptionsResolver;


class TemplateType extends AbstractType {
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
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
		->add ( 'editWithWysiwyg', CheckboxType::class, [
			'required' => false,
		])
		->add ( 'preview', CheckboxType::class, [
			'required' => false,
			'label' => 'Preview (exports)',
		])
		->add('role', RolePickerType::class)
		->add ( 'active', CheckboxType::class, [
				'required' => false,
				'label' => 'Active',
		])
		
		->add( 'renderOption', RenderOptionType::class, [
				'required' => true,
		])
		->add( 'downloadResultUrl', CheckboxType::class, [
				'required' => false,
		])
		->add( 'mimeType', TextType::class, [
				'required' => false,
		])
		->add( 'filename', TextareaType::class, [
				'required' => false,
				'attr' => [
						'class' => $template->getEditWithWysiwyg()?'ckeditor':''
				],
		])
		->add( 'extension', TextType::class, [
				'required' => false,
		])
		->add ( 'body', TextareaType::class, [
			'required' => false,
			'attr' => [
				'class' => $template->getEditWithWysiwyg()?'ckeditor':''
			]
		])
 		->add('roleCc', RolePickerType::class)
		->add('roleTo', RolePickerType::class)
		->add('circlesTo', ObjectPickerType::class, [
				'required' => false,
				'type' => $options['type'],
				'multiple' => true,
		])
		->add( 'responseTemplate', TextType::class, [
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
