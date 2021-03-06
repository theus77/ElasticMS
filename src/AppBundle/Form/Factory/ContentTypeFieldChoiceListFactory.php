<?php
namespace AppBundle\Form\Factory;

use AppBundle\Form\Field\ContentTypeFieldChoiceLoader;
use AppBundle\Form\Field\ObjectChoiceLoader;
use AppBundle\Service\ContentTypeService;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;


class ContentTypeFieldChoiceListFactory extends DefaultChoiceListFactory{

	/**@var ContentTypeService $contentTypesService*/
	private $contentTypesService;

	/**
     * constructor called by the service mechanisme
     */
    public function __construct( ContentTypeService $contentTypesService ){
		$this->contentTypesService = $contentTypesService;
	}
    
    /**
     * instanciate a ObjectChoiceLoader (with the required services)
     */
    public function createLoader(array $mapping, array $types, $firstLevelOnly){
    	return new ContentTypeFieldChoiceLoader($mapping, $types, $firstLevelOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function createListFromLoader(ChoiceLoaderInterface $loader, $value = null) {
    	return $loader->loadChoiceList($value);
    }
}