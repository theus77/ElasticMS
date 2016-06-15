<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Field\AssetType;
	
/**
 * Defined a Container content type.
 * It's used to logically groups subfields together. However a Container is invisible in Elastic search.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *
 */
class AssetFieldType extends DataFieldType {

	/**
	 * Get a icon to visually identify a FieldType
	 *
	 * @return string
	 */
	public static function getIcon(){
		return 'fa fa-file-o';
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'File field';
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		/** @var FieldType $fieldType */
		$fieldType = $options ['metadata'];
		$builder->add ( 'raw_data', AssetType::class, [
				'label' => (null != $options ['label']?$options ['label']:$fieldType->getName()),
				'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole())
		] );
	}
}