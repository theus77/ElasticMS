<?php

namespace AppBundle\Form\View;

use AppBundle\Entity\DataField;
use AppBundle\Entity\View;
use AppBundle\Form\View\ViewType;
use Elasticsearch\Client;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use AppBundle\Form\View\Criteria\CriteriaFilterType;
use AppBundle\Entity\Form\CriteriaUpdateConfig;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class CriteriaViewType extends ViewType {

	/** @var \Twig_Environment twig*/
	private $twig;

	/** @var Client $client */
	private $client;
	
	/** @var Router $router */
	private $router;
	
	public function __construct($twig, $client, $router){
		$this->twig = $twig;
		$this->client = $client;
		$this->router = $router;
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
	public function getParameters(View $view, FormFactoryInterface $formFactoty) {
		
		$criteriaUpdateConfig = new CriteriaUpdateConfig($view);
		
		//dump(get_class($this->router));
		$form = $formFactoty->create(CriteriaFilterType::class, $criteriaUpdateConfig, [
				'view' => $view,
				'method' => 'GET',
				'action' => $this->router->generate('views.criteria.table', [
					'view' => 	$view->getId(),
				]),
		]);
		
		
		
		$categoryField = false;
		if($view->getContentType()->getCategoryField()) {
			$categoryField = $view->getContentType()->getFieldType()->__get('ems_'.$view->getContentType()->getCategoryField());
		}
		
		return [
			'criteriaField' => 	$view->getOptions()['criteriaField'],
			'categoryField' => 	$categoryField,
			'view' => $view,
			'contentType' => $view->getContentType(),
			'environment' => $view->getContentType()->getEnvironment(),
			'criterionList' => $view->getContentType()->getFieldType()->__get('ems_'.$view->getOptions()['criteriaField']),
			'form' => $form->createView(),
		];
	}
	
}