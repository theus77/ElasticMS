<?php

namespace AppBundle\Form\Field;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class ColorPickerFullType extends TextType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'colorpicker';
	}
	
}
