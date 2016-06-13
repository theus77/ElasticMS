<?php
namespace AppBundle\Form\Factory;

use AppBundle\Form\Field\ObjectChoiceLoader;
use AppBundle\Service\ContentTypeService;
use Elasticsearch\Client;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use AppBundle\Form\Field\ObjectChoiceDynamicLoader;
use AppBundle\Form\Field\ObjectChoiceStaticLoader;


class ObjectChoiceListFactory extends DefaultChoiceListFactory{

	private $client;
	/**@var Session $session*/
	private $session;
	/**@var ContentTypeService $contentTypes*/
	private $contentTypes;

	/**
     * constructor called by the service mechanisme
     */
    public function __construct(
    		Client $client,
			Session $session, 
			ContentTypeService $contentTypes){
		$this->client = $client;
		$this->session = $session;
		$this->contentTypes = $contentTypes;
	}
    
    /**
     * instanciate a ObjectChoiceLoader (with the required services)
     */
    public function createDynamicLoader($types = null, $dynamicLoad = true){
    	if(null === $types){
    		$types = $this->contentTypes->getAllAliases();
    	}
    	return new ObjectChoiceDynamicLoader($this->client, $this->session, $this->contentTypes, $types);
    }
    
    /**
     * instanciate a ObjectChoiceLoader (with the required services)
     */
    public function createStaticLoader($types = null){
    	if(null === $types){
    		$types = $this->contentTypes->getAllAliases();
    	}
    	return new ObjectChoiceStaticLoader($this->client, $this->session, $this->contentTypes, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function createListFromLoader(ChoiceLoaderInterface $loader, $value = null)
    {
    	return $loader->loadChoiceList($value);
    }
}