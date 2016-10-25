<?php

namespace AppBundle\Repository;

/**
 * I18nRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class I18nRepository extends \Doctrine\ORM\EntityRepository
{
	public function count($identifier) {
		$qb = $this->createQueryBuilder('i')
		->select('COUNT(i)');
		
		if($identifier != null){
			$qb->where('i.identifier LIKE :identifier')
			->setParameter('identifier', '%' . $identifier .'%');
		}
		
		return $qb->getQuery()
		->getSingleScalarResult();
	}
	
	public function findByWithFilter($limit, $from, $identifier) {
		
		$qb = $this->createQueryBuilder('i')
		->select('i');
		
		if($identifier != null){
			$qb->where('i.identifier LIKE :identifier')
			->setParameter('identifier', '%' . $identifier .'%');
		}
		
		$qb->orderBy('i.identifier', 'ASC')
		->setFirstResult($from)
		->setMaxResults($limit);
		
		return $qb->getQuery()->getResult();
	}
}
