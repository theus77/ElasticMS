<?php

namespace AppBundle\Repository;

/**
 * FieldTypeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TemplateRepository extends \Doctrine\ORM\EntityRepository
{
	/**
	 *  Retrieve all Template by a render_option defined
	 *  
	 *  @param String option
	 */
	public function findByRenderOption($option) {
		
		$qb = $this->createQueryBuilder('t')
		->select('t')
		->where('t.render_option = \':option\'')
		->setParameter('option', $option);
		$query = $qb->getQuery();

		$results = $query->getArrayResult();
	
		return $results;
	}
}
