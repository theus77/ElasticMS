<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Revision;
use AppBundle\Entity\Environment;
use AppBundle\Entity\ContentType;

/**
 * RevisionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RevisionRepository extends \Doctrine\ORM\EntityRepository
{
	
	public function draftCounterGroupedByContentType() {

		$qb = $this->createQueryBuilder('r');
		$qb->select('c.id content_type_id', 'count(c.id) counter');
		$qb->join('r.contentType', 'c');
		$and = $qb->expr()->andX();
		$and->add($qb->expr()->eq('r.deleted', 0));
		$and->add($qb->expr()->eq('r.draft', true));
		$qb->where($and);
		$qb->groupBy('c.id');
		
		return $qb->getQuery()->getResult();
	}

	public function countByContentType(ContentType $contentType) {
		return $this->createQueryBuilder('a')
		->select('COUNT(a)')
		->where('a.contentType = :contentType')
		->setParameter('contentType', $contentType)
		->getQuery()
		->getSingleScalarResult();
	}
	
	public function getAllRevisionsSummary($ouuid, ContentType $contentType) {
	
		$qb = $this->createQueryBuilder('r');
		$qb->select('r', 'e');
		$qb->leftJoin('r.environments', 'e');
		$qb->where($qb->expr()->eq('r.ouuid', ':ouuid'));
		$qb->andWhere($qb->expr()->eq('r.contentType', ':contentType'));
		$qb->orderBy('r.startTime', 'ASC');
		$qb->setParameter('ouuid', $ouuid);
		$qb->setParameter('contentType', $contentType);
	
		return $qb->getQuery()->getResult();
	}

	public function findByOuuidContentTypeAndEnvironnement(Revision $revision, Environment $env=null) {
	
		if(!isset($env)){
			$env = $revision->getContentType()->getEnvironment();
		}
		
		$qb = $this->createQueryBuilder('r');
		$qb->join('r.environments', 'e');
		$qb->where('r.ouuid = :ouuid and e.id = :envId and r.contentType = :contentTypeId');
		$qb->setParameters([
				'ouuid' => $revision->getOuuid(),
				'envId' => $env->getId(),
				'contentTypeId' => $revision->getContentType()->getId()
		]);
	
		return $qb->getQuery()->getResult();
	}
	

	public function lockRevision($revisionId, $username, $lockUntil) {
		$qb = $this->createQueryBuilder('r')->update('Revision', 'r') 
			->set('r.lock_by', $username) 
			->set('r.lock_until', $username) 
			->where('r.id = ?1')->setParameter(1, $revisionId);
		
	}

	public function finaliseRevision($contentTypeId, $ouuid, $now) {
		$qb = $this->createQueryBuilder('r')->update('Revision', 'r')
			->set('r.end_time', $now)
			->where('r.content_type_id = ?1')
			->andWhere('r.ouuid = ?2')
			->andWhere('r.end_time is null')
			->setParameter(1, $contentTypeId)
			->setParameter(2, $ouuid);
	
	}
}
