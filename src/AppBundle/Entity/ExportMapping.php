<?php

namespace AppBundle\Entity;

use AppBundle\Form\Field\RenderOptionType;

/**
 * Object to store the mapping between contentTypes and templates used for export functionality
 * This is an in memory object that should not be persisted to the database.
 */
class ExportMapping
{
    /**
     * @var array
     *
     */
    private $mapping;
    
    /**
     * @var RenderOptionType::constant
     *
     */
    //private $templateType;

    public function __construct(){
    	$this->mapping = [];
    }
    
    /**
     * Set templateType
     *
     * @param string $templateType
     *
     * @return ContentTypeTemplateMapping
     */
    /*public function setTemplateType($templateType)
    {
    	$this->templateType = $templateType;
    
    	return $this;
    }*/
    
    /**
     * Get templateType
     *
     * @return string
     */
    /*public function getTemplateType()
    {
    	return $this->templateType;
    }*/
    
    /**
     * Add templates for contentTypes
     *
     */
    public function addTemplates($results, $index0 = '_type', $index1= '_index')
    {
    	foreach ($results['hits']['hits'] as $result){
    		// [ContentTypeName, IndexName]
    		$element = array(
    					$result[$index0],
    					$result[$index1],
    		);
    	
    		if (!in_array($element, $this->mapping)){
    			$this->mapping[$result[$index0]] =  $element;
    		}
    	}
    }
    
    /**
     * Get contentTypeNames
     *
     * @return string
     */
    public function getContentTypeNames()
    {
    	$contentTypes = [];
    	foreach ($this->mapping as $element){
    		// [ContentTypeName, IndexName]
    		$contentTypes[] = $element[0];
    	}
    		
    	return $contentTypes;
    }
    
	/**
     * Get findTemplate
     *
     * @return string
     */
    public function findTemplate($contentTypeName, $indexName)
    {
    	return null;
    }
    
    /**
     * Get findUniqueName
     *
     * @return string
     */
    public function getCombinedName($name)
    {
    	$element = $this->mapping[$name];
    	return $element[0].'_'.$element[1];
    }
}
