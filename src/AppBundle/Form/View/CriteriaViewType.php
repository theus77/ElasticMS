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
		return "Criteria: a view where we can update criterion content types";
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
		->add ( 'criterionList', TextareaType::class, [
				'label' => 'List of criterion field'
		] )
		->add ( 'dataField', TextType::class, [
				'label' => 'The field managed by the criteria'
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
		

		$criterionList = explode("\n", str_replace("\r", "", $view->getStructuredOptions()['criterionList']));
		
		return [
			'criterionList' => 	$criterionList,
			'dataField' => 	$view->getStructuredOptions()['dataField'],
			'view' => $view,
			'contentType' => $view->getContentType(),
			'environment' => $view->getContentType()->getEnvironment(),
		];
	}
	
}