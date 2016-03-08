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
		$resolver->setDefault ( 'suffixIcon', null );
		$resolver->setDefault ( 'suffixText', null );
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
		$view->vars ['suffixIcon'] = $options ['suffixIcon'];
		$view->vars ['suffixText'] = $options ['suffixText'];
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
