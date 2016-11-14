<?php

namespace AppBundle\Form\View;

use AppBundle\Entity\DataField;
use AppBundle\Entity\View;
use AppBundle\Form\View\ViewType;
use Elasticsearch\Client;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class ReportViewType extends ViewType {


	/**@var \Twig_Environment $twig*/
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
		return "Report: perform an elasticsearch query and generate a report with a twig template";
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getName(){
		return "Report";
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder
		->add ( 'body', TextareaType::class, [
				'label' => 'The Elasticsearch body query [JSON Twig]',
				'attr' => [
					'rows' => 10,
				]
		] )
		->add ( 'size', IntegerType::class, [
				'label' => 'Limit the result to the x first results',
		] )
		->add ( 'template', TextareaType::class, [
				'label' => 'The Twig template used to display each keywords',
				'attr' => [
					'rows' => 10,
				]
		] );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'report_view';
	}
	

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getParameters(View $view, FormFactoryInterface $formFactoty, Request $request) {
		
		$searchQuery = [
			'index' => $view->getContentType()->getEnvironment()->getAlias(),
			'type' => $view->getContentType()->getName(),
			'body' => $view->getOptions()['body'],
			'size' => $view->getOptions()['size'],
		];
		
		$result = $this->client->search($searchQuery);
		
		try {
			$render = $this->twig->createTemplate($view->getOptions()['template'])->render([
				'view' => $view,
				'contentType' => $view->getContentType(),
				'environment' => $view->getContentType()->getEnvironment(),
				'result' => $result,
			]);
		}
		catch (\Exception $e){
			$render = "Something went wrong with the template of the view ".$view->getName()." for the content type ".$view->getContentType()->getName()." (".$e->getMessage().")";
		}
		
		return [
			'render' => $render,
			'view' => $view,
			'contentType' => $view->getContentType(),
			'environment' => $view->getContentType()->getEnvironment(),
		];
	}
	
}