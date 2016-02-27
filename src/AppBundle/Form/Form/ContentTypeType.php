<?php

namespace AppBundle\Form\Form;

use AppBundle\Entity\ContentType;
use AppBundle\Form\Field\ColorPickerType;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ContentTypeType extends AbstractType {
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
    public function buildForm(FormBuilderInterface $builder, array $options) {
    	
    	
		/** @var ContentType $contentType */
		$contentType = $builder->getData ();

		$builder->add ( 'rootContentType');
		$builder->add ( 'active');
		$builder->add ( 'labelField');
		$builder->add ( 'parentField');
		$builder->add ( 'userField');
		$builder->add ( 'dateField');
		$builder->add ( 'startDateField');
		$builder->add ( 'endDateField');
		$builder->add ( 'locationField');
		$builder->add ( 'ouuidField');
		$builder->add ( 'imageField');
		$builder->add ( 'videoField');
		$builder->add ( 'categoryField');
		$builder->add ( 'pluralName', TextType::class);
		$builder->add ( 'icon', IconPickerType::class);
		$builder->add ( 'orderKey', IntegerType::class);
		$builder->add ( 'color', ColorPickerType::class);
		
		
		$builder->add ( 'description', TextareaType::class, [
				'attr' => [
						'class' => 'ckeditor'
				]
		] );
		$builder->add ( 'indexTwig', TextareaType::class, [
				'attr' => [
						'class' => 'ckeditor'
				]
		] );
		
		
		$builder->add ( 'save', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'fa fa-save' 
		] );
		
		return parent::buildForm($builder, $options);
		 
	}
}
