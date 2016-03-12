<?php

namespace AppBundle\Form\View;

use AppBundle\Entity\DataField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Form\View\ViewType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class KeywordsViewType extends ViewType {

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return "a view where all properties of kind (such as keyword) are listed on a single page";
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getName(){
		return "Keywords";
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
		->add ( 'aggsQuery', TextareaType::class, [
				'label' => 'The aggregations Elasticsearch query [Twig]'
		] );
	}
	
}