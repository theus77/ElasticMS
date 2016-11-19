<?php

namespace AppBundle\Repository;

use AppBundle\Entity\ContentType;
use AppBundle\Entity\Environment;
use AppBundle\Entity\Revision;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * RevisionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RevisionRepository extends \Doctrine\ORM\EntityRepository
{

	public function findByEnvironment($ouuid, ContentType $contentType, Environment $environment){
		$qb = $this->createQueryBuilder('r')
			->join('r.environments', 'e')
			->andWhere('r.ouuid = :ouuid')
			->andWhere('e.id = :eid')
			->setParameter('ouuid', $ouuid)
			->setParameter('eid', $environment->getId());
		
		return $qb->getQuery()->getSingleResult();
	}
	
	public function draftCounterGroupedByContentType($circles, $isAdmin) {
		$parameters = [];
		$qb = $this->createQueryBuilder('r');

		$draftConditions = $qb->expr()->andX();
		$draftConditions->add($qb->expr()->eq('r.draft', true));		
		$draftConditions->add($qb->expr()->isNull('r.endTime'));
		
		$draftOrAutosave = $qb->expr()->orX();
		$draftOrAutosave->add($draftConditions);
		$draftOrAutosave->add($qb->expr()->isNotNull('r.autoSave'));
		
		$qb->select('c.id content_type_id', 'count(c.id) counter');
		$qb->join('r.contentType', 'c');
		$and = $qb->expr()->andX();
		$and->add($qb->expr()->eq('r.deleted', 0));
		$and->add($draftOrAutosave);
		if(!$isAdmin){
			$inCircles = $qb->expr()->orX();
			$inCircles->add($qb->expr()->isNull('r.circles'));
			foreach ($circles as $counter => $circle){				
				$inCircles->add($qb->expr()->like('r.circles', ':circle'.$counter));
				$parameters['circle'.$counter] = '%'.$circle.'%';
			}
			$and->add($inCircles);
		}
		$qb->where($and);
		$qb->groupBy('c.id');
		$qb->setParameters($parameters);
		return $qb->getQuery()->getResult();
	}
	
	public function findInProgresByContentType($contentType, $circles, $isAdmin) {

		$parameters = ['contentType' => $contentType];
		
		$qb = $this->createQueryBuilder('r');

		$draftConditions = $qb->expr()->andX();
		$draftConditions->add($qb->expr()->eq('r.draft', true));		
		$draftConditions->add($qb->expr()->isNull('r.endTime'));
		
		$draftOrAutosave = $qb->expr()->orX();
		$draftOrAutosave->add($draftConditions);
		$draftOrAutosave->add($qb->expr()->isNotNull('r.autoSave'));
		
		$and = $qb->expr()->andX();
		$and->add($qb->expr()->eq('r.deleted', 0));
		$and->add($draftOrAutosave);
		
		if(!$isAdmin){
			$inCircles = $qb->expr()->orX();
			$inCircles->add($qb->expr()->isNull('r.circles'));
			foreach ($circles as $counter => $circle){				
				$inCircles->add($qb->expr()->like('r.circles', ':circle'.$counter));
				$parameters['circle'.$counter] = '%'.$circle.'%';
			}
			$and->add($inCircles);
		}
		

		$qb->where($and)
			->andWhere($qb->expr()->eq('r.contentType', ':contentType'));

		$qb->setParameters($parameters);
		return $qb->getQuery()->getResult();
	}
	
	
	public function countDifferencesBetweenEnvironment($source, $target) {
		$sql = 'select count(*) foundRows from (select r.ouuid from environment_revision e, revision r,  content_type ct where e.environment_id in ('.$source.' ,'.$target.') and r.id = e.revision_id and ct.id = r.`content_type_id` and ct.deleted = 0 group by ct.id, r.ouuid, ct.orderKey having count(*) = 1 or max(r.`id`) <> min(r.`id`)) tmp';
		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('foundRows', 'foundRows');
		$query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
		$foundRows = $query->getResult();
		
		return $foundRows[0]['foundRows'];
	}
	
	public function compareEnvironment($source, $target, $from, $limit) {
		
		$qb = $this->createQueryBuilder('r')
			->select('c.id', 'c.color', 'c.labelField', 'c.name content_type_name', 'c.icon', 'r.ouuid', 'count(c.id) counter', 'min(concat(e.id, \'/\',r.id, \'/\', r.created)) minrevid', 'max(concat(e.id, \'/\',r.id, \'/\', r.created)) maxrevid')
			->join('r.contentType', 'c')
			->join('r.environments', 'e')
			->where('e.id in (?1, ?2)')
			->andWhere('r.deleted = 0')
			->andWhere('c.deleted = 0')
			->groupBy('c.id', 'c.name', 'c.icon', 'r.ouuid', 'c.orderKey')
			->orHaving('count(r.id) = 1')
			->orHaving('max(r.id) <> min(r.id)')
			->addOrderBy('c.orderKey')
			->addOrderBy('r.ouuid')
			->setFirstResult($from)
			->setMaxResults($limit)
			->setParameter(1, $source,  \Doctrine\DBAL\Types\Type::INTEGER)
			->setParameter(2, $target,  \Doctrine\DBAL\Types\Type::INTEGER);		
		
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

	public function countRevisions($ouuid, ContentType $contentType) {
		$qb = $this->createQueryBuilder('r')
			->select('COUNT(r)');
		$qb->where($qb->expr()->eq('r.ouuid', ':ouuid'));
		$qb->andWhere($qb->expr()->eq('r.contentType', ':contentType'));
		$qb->setParameter('ouuid', $ouuid);
		$qb->setParameter('contentType', $contentType);

		return $qb->getQuery()->getSingleScalarResult();
	}
	
	public function revisionsLastPage($ouuid, ContentType $contentType) {
		return floor($this->countRevisions($ouuid, $contentType)/5.0)+1;
	}
	
	public function firstElemOfPage($page) {
		return ($page-1)*5;
	}
	
	
	
	public function getAllRevisionsSummary($ouuid, ContentType $contentType, $page=1) {
		$qb = $this->createQueryBuilder('r');
		$qb->select('r', 'e');
		$qb->leftJoin('r.environments', 'e');
		$qb->where($qb->expr()->eq('r.ouuid', ':ouuid'));
		$qb->andWhere($qb->expr()->eq('r.contentType', ':contentType'));
		$qb->setMaxResults(5);
		$qb->setFirstResult(($page-1)*5);
		$qb->orderBy('r.created', 'DESC');
		$qb->setParameter('ouuid', $ouuid);
		$qb->setParameter('contentType', $contentType);
	
		return $qb->getQuery()->getResult();
	}

	public function findByOuuidContentTypeAndEnvironnement(Revision $revision, Environment $env=null) {
		if(!isset($env)){
			$env = $revision->getContentType()->getEnvironment();
		}
		
		return $this->findByOuuidAndContentTypeAndEnvironnement($revision->getContentType(), $revision->getOuuid(), $env);
	}
	
	public function findByOuuidAndContentTypeAndEnvironnement(ContentType $contentType, $ouuid, Environment $env) {
	
		
		$qb = $this->createQueryBuilder('r');
		$qb->join('r.environments', 'e');
		$qb->where('r.ouuid = :ouuid and e.id = :envId and r.contentType = :contentTypeId');
		$qb->setParameters([
				'ouuid' => $ouuid,
				'envId' => $env->getId(),
				'contentTypeId' => $contentType->getId()
		]);
	
		return $qb->getQuery()->getResult();
	}
	
	public function lockRevision($revisionId, $username,\DateTime $lockUntil) {
		$qb = $this->createQueryBuilder('r')->update() 
			->set('r.lockBy', '?1') 
			->set('r.lockUntil', '?2') 
			->where('r.id = ?3')
			->setParameter(1, $username)
			->setParameter(2, $lockUntil, \Doctrine\DBAL\Types\Type::DATETIME)
			->setParameter(3, $revisionId);
		return $qb->getQuery()->execute();
	}

	public function finaliseRevision(ContentType $contentType, $ouuid,\DateTime $now) {
		$qb = $this->createQueryBuilder('r')->update()
			->set('r.endTime', '?1')
			->where('r.contentType = ?2')
			->andWhere('r.ouuid = ?3')
			->andWhere('r.endTime is null')
			->andWhere('r.lockBy  <> ?4 OR r.lockBy is null')
			->setParameter(1, $now, \Doctrine\DBAL\Types\Type::DATETIME)
			->setParameter(2, $contentType)
			->setParameter(3, $ouuid)
			->setParameter(4, "SYSTEM_MIGRATE");
			return $qb->getQuery()->execute();
	
	}
	
	public function getCurrentRevision(ContentType $contentType, $ouuid)
	{
		$em = $this->getEntityManager();
		$qb = $this->createQueryBuilder('r')->select()
			->where('r.contentType = ?2')
			->andWhere('r.ouuid = ?3')
			->andWhere('r.endTime is null')
			->setParameter(2, $contentType)
			->setParameter(3, $ouuid);
		
		/**@var Revision[] $currentRevision*/
		$currentRevision = $qb->getQuery()->execute();
		if(isset($currentRevision[0])) {
			return $currentRevision[0];
		} else {
			return null;
		}
	}
	
	public function publishRevision(Revision $revision) {
		$qb = $this->createQueryBuilder('r')->update()
		->set('r.draft', 0)
		->set('r.lockBy', "null")
		->set('r.lockUntil', "null")
		->set('r.endTime', "null")
		->where('r.id = ?1')
		->setParameter(1, $revision->getId());
		
		return $qb->getQuery()->execute();
		
	}
	
	public function deleteRevision(Revision $revision) {
		$qb = $this->createQueryBuilder('r')->update()
		->set('r.delete', 1)
		->where('r.id = ?1')
		->setParameter(1, $revision->getId());
			
		return $qb->getQuery()->execute();
	}
	
	public function deleteRevisions(ContentType $contentType=null) {
		if($contentType == null) {
			$qb = $this->createQueryBuilder('r')->update()
			->set('r.delete', 1);
			
			return $qb->getQuery()->execute();
		} else {
			$qb = $this->createQueryBuilder('r')->update()
			->set('r.delete', 1)
			->where('r.contentTypeId = ?1')
			->setParameter(1, $contentType->getId());
			
			return $qb->getQuery()->execute();
		}
	}
}
