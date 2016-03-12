<?php

namespace AppBundle\Form\View;

use AppBundle\Entity\DataField;
use AppBundle\Form\View\ViewType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class KeywordsViewType extends ViewType {

	private $twig;
	
	public function __construct($twig){
		$this->twig = $twig;
	}
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return "Keywords: a view where all properties of kind (such as keyword) are listed on a single page";
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
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'keywords_view';
	}
	
}