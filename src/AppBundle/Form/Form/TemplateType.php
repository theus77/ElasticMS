<?php

namespace AppBundle\Form\Form;

use AppBundle\Entity\Template;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Field\RenderOptionType;


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
		->add( 'renderOption', RenderOptionType::class, [
				'required' => true,
		])
		->add ( 'body', TextareaType::class, [
			'required' => false,
			'attr' => [
				'class' => $template->getEditWithWysiwyg()?'ckeditor':''
			]
		])
		->add ( 'save', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'fa fa-save' 
		] );
	}
}
