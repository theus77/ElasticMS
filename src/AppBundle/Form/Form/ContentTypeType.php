<?php

namespace AppBundle\Form\Form;

use AppBundle\Entity\ContentType;
use AppBundle\Form\Field\ColorPickerType;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Form\FieldType\FieldTypeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\Field\RolePickerType;

class ContentTypeType extends AbstractType {
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
    public function buildForm(FormBuilderInterface $builder, array $options) {
    	
    	
		/** @var ContentType $contentType */
		$contentType = $builder->getData ();

		$builder->add ( 'labelField');
		$builder->add ( 'colorField');
// 		$builder->add ( 'parentField');
// 		$builder->add ( 'userField');
// 		$builder->add ( 'dateField');
// 		$builder->add ( 'startDateField');
		$builder->add ( 'circlesField');
		$builder->add ( 'emailField');
		$builder->add ( 'refererFieldName');
		$builder->add ( 'categoryField');
		$builder->add ( 'editTwigWithWysiwyg', CheckboxType::class, [
			'label' => 'Edit the Twig template with a WYSIWYG editor',
			'required' => false,
		]);
		$builder->add ( 'imageField');
		$builder->add ( 'assetField');
		$builder->add ( 'orderField');
		$builder->add ( 'sortBy');
		$builder->add ( 'pluralName', TextType::class);
		$builder->add ( 'icon', IconPickerType::class, [
			'required' => false,
		]);
		$builder->add ( 'color', ColorPickerType::class, [
			'required' => false,
		]);
		
		
		$builder->add ( 'description', TextareaType::class, [
				'required' => false,
				'attr' => [
						'class' => 'ckeditor'
				]
		] );
		$builder->add ( 'indexTwig', TextareaType::class, [
				'required' => false,
				'attr' => [
						'class' => $options['twigWithWysiwyg']?'ckeditor':''
				]
		] );
		$builder->add ( 'extra', TextareaType::class, [
				'required' => false,
				'attr' => [
						'rows' => 8,
				]
		] );
		
		
		$builder->add ( 'save', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'fa fa-save'
		] );		
		$builder->add ( 'saveAndClose', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'fa fa-save'
		] );		

		$builder->add ( 'rootContentType');
		
		if($contentType->getEnvironment()->getManaged()){
			$builder->add ( 'askForOuuid', CheckboxType::class, [
				'label' => 'Ask for OUUID',
				'required' => false,
			]);
			$builder->add ( 'createRole', RolePickerType::class);
			$builder->add ( 'editRole', RolePickerType::class);
			$builder->add ( 'saveAndEditStructure', SubmitEmsType::class, [ 
					'attr' => [ 
							'class' => 'btn-primary btn-sm ' 
					],
					'icon' => 'fa fa-save'
			] );
		}
		
		return parent::buildForm($builder, $options);
		 
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefault ( 'twigWithWysiwyg', true );
	}
	
}
