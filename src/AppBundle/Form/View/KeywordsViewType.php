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
class KeywordsViewType extends ViewType {

	
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
		] )
		->add ( 'template', TextareaType::class, [
				'label' => 'The Twig template used to display each keywords'
		] )
		->add ( 'pathToBuckets', TextType::class, [
				'label' => 'The twig path to the buckets array'
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
	

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getParameters(View $view) {
		
		$searchQuery = [
			'index' => $view->getContentType()->getEnvironment()->getAlias(),
			'type' => $view->getContentType()->getName(),
			'search_type' => 'count',
			'body' => $view->getOptions()['aggsQuery']
		];
		
		$retDoc = $this->client->search($searchQuery);
		
		foreach ( explode('.', $view->getOptions()['pathToBuckets']) as $attribute ){
			$retDoc = $retDoc[$attribute];
		}
		
		return [
			'keywords' => $retDoc,
			'view' => $view,
			'contentType' => $view->getContentType(),
			'environment' => $view->getContentType()->getEnvironment(),
		];
	}
	
}