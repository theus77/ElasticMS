<?php
namespace AppBundle\Form\Factory;

use AppBundle\Form\Field\ObjectChoiceLoader;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Elasticsearch\Client;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;


class ObjectChoiceListFactory extends DefaultChoiceListFactory{

	private $client;
	private $doctrine;

	/**
     * constructor called by the service mechanisme
     */
    public function __construct(Client $client, Registry $doctrine){
		$this->client = $client;
		$this->doctrine = $doctrine;
	}
    
    /**
     * instanciate a ObjectChoiceLoader (with the required services)
     */
    public function createLoader(){
    	return new ObjectChoiceLoader($this->client, $this->doctrine);
    }

    /**
     * {@inheritdoc}
     */
    public function createListFromLoader(ChoiceLoaderInterface $loader, $value = null)
    {
    	return $loader->loadChoiceList($value);
    }
}