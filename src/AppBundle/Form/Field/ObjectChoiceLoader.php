<?php 

namespace AppBundle\Form\Field;

use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Elasticsearch\Client;
use Doctrine\Bundle\DoctrineBundle\Registry;

class ObjectChoiceLoader implements ChoiceLoaderInterface {
	
	private $objectChoiceList;
	
	public function __construct(Client $client, Registry $doctrine){
		$this->objectChoiceList = new ObjectChoiceList($client, $doctrine);
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