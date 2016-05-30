<?php

namespace AppBundle\Repository;

/**
 * JobRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class JobRepository extends \Doctrine\ORM\EntityRepository
{	
	public function countJobs() {
		return $this->createQueryBuilder('a')
		 ->select('COUNT(a)')
		 ->getQuery()
		 ->getSingleScalarResult();
	}
}
