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
	private $contentTypes;
	private $loadAll;
	
	
	public function __construct(Client $client, Registry $doctrine){
		$this->choices = [];		
		
		$this->client = $client;
		$this->doctrine = $doctrine;	
		
		$repository = $this->doctrine->getRepository('AppBundle:ContentType');
    	$this->contentTypes = $repository->findAllAsAssociativeArray();
    	$this->loadAll = false;
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
	
	public function loadAllChoices($index, $type){
		$params = [
				'size' => 500
		];
		if(isset($index)){
			$params['index'] = $index;
		}
		if(isset($type)){
			$params['type'] = $type;
		}
		$items = $this->client->search($params);
		
		//TODO pagination sur toutes les pages
		foreach ($items['hits']['hits'] as $hit){
			$listItem = $this->decorate($hit);
			if($listItem){
				$this->choices[$listItem->getKey()] = $listItem	;								
			}
		}
		
	}
	
	/**
	 * intiate (or re-initiate) the choices array based on the list of key passed in parameter
	 * 
	 * @param array $choices
	 */
	public function loadChoices(array $choices){
		$this->choices = [];
		foreach ($choices as $choice){
			
			if(null == $choice){
				//TODO: nothing to load for null. BUt is it normal to pass by?
			}
			else if(is_string($choice)){
				if(strpos($choice, ':') !== false){
					$ref = explode(':', $choice);
					if(isset($this->contentTypes[$ref[0]])){
						/** @var \AppBundle\Entity\ContentType $contentType */
						$contentType = $this->contentTypes[$ref[0]];
						$item = $this->client->get([
								'id' => $ref[1],
								'type' => $ref[0],
								'index' => $contentType->getEnvironment()->getAlias(),
						]);
						
						$listItem = $this->decorate($item);
						if($listItem){
							$this->choices[$choice] = $listItem	;								
						}
						
					}
				}
			}
			else if (get_class($choice) === ObjectChoiceListItem::class){
				$this->choices[$choice->getKey()] = $choice;
			}
			else{
				throw new \Exception('Unknow type of object choice list item: '.get_class($choice));
			}
		}
	}
	
	private function decorate(array $item){
		$out =  new ObjectChoiceListItem($item);
		if(isset($this->contentTypes[$item['_type']])){
			$out->setContentType($this->contentTypes[$item['_type']]);
			return $out;
		}
		return false;
	}
}