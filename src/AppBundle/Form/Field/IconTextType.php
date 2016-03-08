<?php

namespace AppBundle\Form\Field;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IconTextType extends TextType {
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults ( array (
				'compound' => false,
				'metadata' => null,
				'icon' => 'fa fa-key',
	        	'class' => null,
		) );
		$resolver->setDefault ( 'prefixText', null );
		$resolver->setDefault ( 'subfixIcon', null );
		$resolver->setDefault ( 'subfixText', null );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		$view->vars ['icon'] = $options ['icon'];
		$view->vars ['class'] = $options ['class'];
		$view->vars ['prefixText'] = $options ['prefixText'];
		$view->vars ['subfixIcon'] = $options ['subfixIcon'];
		$view->vars ['subfixText'] = $options ['subfixText'];
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getParent() {
		return TextType::class;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'icontext';
	}
}
