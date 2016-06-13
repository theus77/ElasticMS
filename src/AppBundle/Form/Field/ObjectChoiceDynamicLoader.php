<?php 

namespace AppBundle\Form\Field;

use AppBundle\Service\ContentTypeService;
use AppBundle\Service\EnvironmentService;
use Elasticsearch\Client;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class ObjectChoiceDynamicLoader implements ChoiceLoaderInterface {
	
	private $objectChoiceList;
	private $environmentService;
	private $alias;
	private $types;
	private $dynamicLoad;
	
	public function __construct(
			Client $client, 
			Session $session, 
			ContentTypeService $contentTypes,
			$types){
		$this->objectChoiceList = new ObjectChoiceList($client, $session, $contentTypes);
	}

	/**
     * {@inheritdoc}
     */
    public function loadChoiceList($value = null){
		return $this->objectChoiceList;
	}

	/**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, $value = null){
		$this->objectChoiceList->loadChoices($values);
		return $values;
	}
	
	/**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, $value = null){		
		$this->objectChoiceList->loadChoices($choices);
		return $choices;
	}
}