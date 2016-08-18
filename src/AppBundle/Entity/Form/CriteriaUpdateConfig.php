<?php
namespace AppBundle\Entity\Form;



use AppBundle\Entity\DataField;
use AppBundle\Entity\View;
use AppBundle\Entity\FieldType;

/**
 * RebuildIndex
 */
class CriteriaUpdateConfig{

	private $columnCriteria;
	
	private $rowCriteria;

	private $category;
	
	private $criterion;


	function __construct(View $view){
		
		$this->criterion = [];
		$contentType = $view->getContentType();
		
		$rootFieldType = $contentType->getFieldType();
		
		if($contentType->getCategoryField() && $rootFieldType->__get('ems_'.$contentType->getCategoryField())){
			$dataField = new DataField();
			$dataField->setFieldType($rootFieldType->__get('ems_'.$contentType->getCategoryField()));
			$this->setCategory($dataField);
		}
		
		$criteriaField = $rootFieldType->__get('ems_'.$view->getOptions()['criteriaField']);
		/** @var FieldType $child */
		foreach ($criteriaField->getChildren() as $child){
			$dataField = new DataField();
			$dataField->setFieldType($child);
			$this->criterion[$child->getName()] = $dataField;			
		}
		
	}
	
	/**
	 * Set the column criteria field name
	 *
	 * @param string $columnCriteria
	 *
	 * @return CriteriaUpdateConfig
	 */
	public function setColumnCriteria($columnCriteria)
	{
		$this->columnCriteria = $columnCriteria;
	
		return $this;
	}
	
	/**
	 * Get the column criteria field name
	 *
	 * @return string
	 */
	public function getColumnCriteria()
	{
		return $this->columnCriteria;
	}	

	/**
	 * Set the row criteria field name
	 *
	 * @param string $rowCriteria
	 *
	 * @return CriteriaUpdateConfig
	 */
	public function setRowCriteria($rowCriteria)
	{
		$this->rowCriteria = $rowCriteria;
	
		return $this;
	}
	
	/**
	 * Get the row criteria field name
	 *
	 * @return string
	 */
	public function getRowCriteria()
	{
		return $this->rowCriteria;
	}	

	/**
	 * Set the category field type
	 *
	 * @param DataField $category
	 *
	 * @return CriteriaUpdateConfig
	 */
	public function setCategory($category)
	{
		$this->category = $category;
	
		return $this;
	}
	
	/**
	 * Get the category field
	 *
	 * @return DataField
	 */
	public function getCategory()
	{
		return $this->category;
	}

    /**
     * Add criterion
     *
     * @param DataField $criterion
     *
     * @return CriteriaUpdateConfig
     */
    public function addCriterion($criterion)
    {
    	if($criterion)
	        $this->criterion[] = $criterion;

        return $this;
    }

    /**
     * Remove criterion
     *
     * @param DataField $criterion
     */
    public function removeCriterion(DataField $criterion)
    {
        $this->criterion->removeElement($criterion);
    }

    /**
     * Get filters
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCriterion()
    {
        return $this->criterion;
    }
}