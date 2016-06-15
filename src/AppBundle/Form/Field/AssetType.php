<?php

namespace AppBundle\Form\Field;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AssetType extends AbstractType {


	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		$builder->add ( 'sha1', TextType::class, [
			'attr' => [
					'class' => 'sha1'
			],
		])
		->add('mimetype', TextType::class, [
			'attr' => [
					'class' => 'type'
			],
		])
		->add('filename', TextType::class, [
			'attr' => [
					'class' => 'name'
			],
		]);
		
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'assettype';
	}
	
}