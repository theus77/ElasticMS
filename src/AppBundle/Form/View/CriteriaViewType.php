<?php

namespace AppBundle\Form\View;

use AppBundle\Entity\DataField;
use AppBundle\Form\View\ViewType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Elasticsearch\Client;
use AppBundle\Entity\View;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class CriteriaViewType extends ViewType {

	
	private $twig;
	
	/** @var Client $client */
	private $client;
	
	public function __construct($twig, $client){
		$this->twig = $twig;
		$this->client = $client;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return "Criteria: a view where we can massively content types having critetira";
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getName(){
		return "Criteria";
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
		->add ( 'criteriaField', TextType::class, [
				'label' => 'The collection field containing the list of criteria (string)'
		] );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'criteria_view';
	}
	

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getParameters(View $view) {
		
		return [
			'criteriaField' => 	$view->getOptions()['criteriaField'],
			'view' => $view,
			'contentType' => $view->getContentType(),
			'environment' => $view->getContentType()->getEnvironment(),
			'criterionList' => $view->getContentType()->getFieldType()->__get($view->getOptions()['criteriaField'])
		];
	}
	
}