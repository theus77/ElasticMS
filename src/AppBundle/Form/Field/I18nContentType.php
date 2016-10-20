<?php

namespace AppBundle\Form\Field;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class I18nContentType extends AbstractType {


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		$builder->add ( 'locale', TextType::class, [
			'required' => true,
		])
		->add('text', TextareaType::class, [
			'required' =>true,
		]);
	}
	
	public function getName()
	{
		return 'I18n Content';
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'i18n_content';
	}
	
}