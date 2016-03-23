<?php 
namespace AppBundle\Form\Field;


use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Elasticsearch\Client;
use Doctrine\Bundle\DoctrineBundle\Registry;

class ObjectChoiceList implements ChoiceListInterface {
	
	/** @var Client $client */
	private $client;
	private $doctrine;
	
	private $choices;
	
	
	public function __construct(Client $client, Registry $doctrine){
		$this->choices = [];		
		
		$this->client = $client;
		$this->doctrine = $doctrine;	
	}
	
	/**
     * {@inheritdoc}
     */
    public function getChoices(){
		return $this->choices;
	}
	
	/**
     * {@inheritdoc}
     */
    public function getValues(){
		return array_keys($this->choices);
	}
	
	/**
     * {@inheritdoc}
     */
    public function getStructuredValues(){
		$out = [[]];
		foreach ($this->choices as $key => $choice){
			$out[0][$key] = $key;
		}
		return $out;
	}
	
	/**
     * {@inheritdoc}
     */
    public function getOriginalKeys(){
		return $this->choices;
	}
	
	/**
     * {@inheritdoc}
     */
    public function getChoicesForValues(array $values){
		$this->loadChoices($values);
		return array_keys($this->choices);
	}
	
	/**
     * {@inheritdoc}
     */
    public function getValuesForChoices(array $choices){
		$this->loadChoices($choices);
		return array_keys($this->choices);
	}
	
	/**
	 * intiate (or re-initiate) the choices array based on the list of key passed in parameter
	 * 
	 * @param array $choices
	 */
	public function loadChoices(array $choices){
		
		$repository = $this->doctrine->getRepository('AppBundle:ContentType');
    	$contentTypes = $repository->findAllAsAssociativeArray();
		$this->choices = [];
		
		foreach ($choices as $choice){
			if(is_array($choice)){
				$choice = $choice[0];
			}
			if(strpos($choice, ':') !== false){
				$ref = explode(':', $choice);
				/** @var \AppBundle\Entity\ContentType $contentType */
				$contentType = $contentTypes[$ref[0]];
				$item = $this->client->get([
						'id' => $ref[1],
						'type' => $ref[0],
						'index' => $contentType->getEnvironment()->getAlias(),
				]);
				$item['_labelField'] = $contentType->getLabelField();
				$item['_typeIcon'] = $contentType->getIcon();
				$this->choices[$choice] = $item;				
			}
		}
	}
}