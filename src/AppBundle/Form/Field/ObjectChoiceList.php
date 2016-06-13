<?php 
namespace AppBundle\Form\Field;


use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Elasticsearch\Client;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\HttpFoundation\Session\Session;
use AppBundle\Service\ContentTypeService;

class ObjectChoiceList implements ChoiceListInterface {
	
	/** @var Client $client */
	private $client;
	/**@var Session $session*/
	private $session;
	/**@var ContentTypeService $contentTypes*/
	private $contentTypes;

	private $types;
	
	public function __construct(
			Client $client, 
			Session $session, 
			ContentTypeService $contentTypes,
			$types = false){
		$this->choices = [];		
		
		$this->client = $client;	
		$this->session = $session;
		$this->contentTypes = $contentTypes;
		$this->types = $types;
	}
	
	/**
     * {@inheritdoc}
     */
    public function getChoices(){
    	if($this->types){
	    	$this->loadAll($this->types);    		
    	}
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
	
	public function loadAll($types){
		$array = [];
		$this->choices = [];
		$cts = explode(',', $types);
		foreach ($cts as $type) {
			$curentType = $this->contentTypes->getByName($type);
			if($curentType){
				if(!isset($array[$curentType->getEnvironment()->getAlias()])){
					$array[$curentType->getEnvironment()->getAlias()] = [];
				}
				$array[$curentType->getEnvironment()->getAlias()][] = $type;
			}
		}
		
		foreach ($array as $envName => $types){
			$params = [
					'size'=>  '500',
					'index'=> $envName,
					'type'=> implode(',', $types)
			];
			//TODO test si > 500...flashbag
				
			$items = $this->client->search($params);
				
			foreach ($items['hits']['hits'] as $hit){
				$listItem = new ObjectChoiceListItem($hit, $this->contentTypes->getByName($hit['_type']));
				$this->choices[$listItem->getValue()] = $listItem;
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
				//TODO: nothing to load for null. But is it normal to pass by?
			}
			else if(is_string($choice)){
				if(strpos($choice, ':') !== false){
					$ref = explode(':', $choice);
					if($this->contentTypes->getByName($ref[0])){
						/** @var \AppBundle\Entity\ContentType $contentType */
						$contentType = $this->contentTypes->getByName($ref[0]);
						try {
							//TODO get this in one query for all choices
							$item = $this->client->get([
									'id' => $ref[1],
									'type' => $ref[0],
									'index' => $contentType->getEnvironment()->getAlias(),
							]);
							$this->choices[$choice] = new ObjectChoiceListItem($item, $this->contentTypes->getByName($item['_type']));
							
						}
						catch(Missing404Exception $e) {
							$this->session->getFlashBag()->add('warning', 'It is impossible to found the object '.$choice);
						}
					}
					else{
						$this->session->getFlashBag()->add('warning', 'Unknowed type of object : '.$ref[0]);
					}
				}
				else{
					$this->session->getFlashBag()->add('warning', 'Malformed object key : '.$choice);
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
}