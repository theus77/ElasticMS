<?php

namespace AppBundle\Repository;

use AppBundle\Entity\ContentType;
use AppBundle\Entity\Notification;
use AppBundle\Entity\User;
use AppBundle\Repository\TemplateRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use AppBundle\Entity\Revision;
use AppBundle\Entity\Environment;
/**
 * NotificationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NotificationRepository extends \Doctrine\ORM\EntityRepository
{
	/**@var AuthorizationCheckerInterface $authorizationChecker*/
	protected $authorizationChecker;
	
	public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker){
		$this->authorizationChecker = $authorizationChecker;
	}
	
	
	
	public function findByRevionsionOuuidAndEnvironment(Revision $revision, Environment $environment){
		$qb = $this->createQueryBuilder('n')
			->select('n')
			->innerJoin('AppBundle:Revision', 'r')
			->where('r.ouuid = :ouuid')
			->andWhere('r.contentType = :contentType')
			->andwhere('r.deleted = 0')
			->andwhere('n.status = :status')
			->andwhere('n.environment = :environment');
		
		$qb->setParameters([
				'status' => "pending",
				'contentType' => $revision->getContentType(),
				'ouuid' => $revision->getOuuid(),
				'environment' => $environment,
		]);
		
		$query = $qb->getQuery();
		
		return $query->getResult();

	}
	
	
	public function countRejectedForUser(User $user, $contentTypes = null, $environments = null, $templates = null) {
		
		$query = $this->createQueryBuilder('n')
		->select('COUNT(n)')
		->where('n.status = :status')
		->andwhere('n.username =  :username');
		$params = array('status' => "rejected", 'username' => $user->getUsername());
		
		
		$query->setParameters($params);
		$result = $query->getQuery()->getSingleScalarResult();
		
		return $result;
	}
	
	
	/**
	 * Count notifications for logged user
	 * 
	 * @param User $user
	 * @return int
	 */
	public function countPendingByUserRoleAndCircle(User $user, $contentTypes = null, $environments = null, $templates = null) {
		
		$templateIds = $this->getTemplatesIdsForUser($user, $contentTypes);
		
		$query = $this->createQueryBuilder('n')
		->select('COUNT(n)')
		->where('n.status = :status')
		->andwhere('n.template IN (:ids)');
		$params = array('status' => "pending", 'ids' => $templateIds);
		
		if($environments != null){
			$query->andWhere('n.environment IN (:envs)');
			$params['envs'] = $environments;			
		}
		if($templates != null){
			$query->andWhere('n.template IN (:templates)');
			$params['templates'] = $templates;
		}
		
		$query->setParameters($params);
		$result = $query->getQuery()->getSingleScalarResult();
		
		return $result;
	}
	
	
	public function countNotificationByUuidAndContentType($ouuid, ContentType $contentType){
		$qb = $this->createQueryBuilder('n')
		->select('count(n)')
		->leftJoin('AppBundle:Revision', 'r', 'WITH', 'n.revision = r.id')
		->where('n.status = :status')
		->andWhere('r.contentType = :contentType')
		->andwhere('r.ouuid = :ouuid');
		
		$qb->setParameters([
				'status' => "pending",
				'contentType' => $contentType,
				'ouuid' => $ouuid,
		]);
		
		$query = $qb->getQuery();
		
		$results = $query->getResult();
		
		return $results[0][1];
	}
	
	/**
	 * Select sent notifications for logged user
	 *
	 * @param User $user
	 * @return array Notification
	 */
	public function findByPendingAndRoleAndCircleForUserSent(User $user, $from, $limit, $contentTypes = null, $environments = null, $templates = null) {
		$templateIds = $this->getTemplatesIdsForUserFrom($user, $contentTypes);
	
		
		$qb = $this->createQueryBuilder('n')
			->select('n')
			->join('n.revision', 'r')
			->where('n.status = :status')
			->andwhere('n.template IN (:ids)')
			->andwhere('r.deleted = 0')
			->andwhere('r.id = n.revision');
		
		$params = array(
					'status' => "pending",
					'ids' => $templateIds
			);
		
		if($environments != null){
			$qb->andWhere('n.environment IN (:envs)');
			$params['envs'] = $environments;			
		}
		if($templates != null){
			$qb->andWhere('n.template IN (:templates)');
			$params['templates'] = $templates;
		}
		
		
		$orCircles = $qb->expr()->orX();
		$orCircles->add('r.circles is null');
		
		$counter = 0;
		foreach ($user->getCircles() as $circle) {
			$orCircles->add('r.circles like :circle_'.$counter);
			$params['circle_'.$counter] = '%'.$circle.'%';
			++$counter;
		}

		
		$qb->andWhere($orCircles);
		
		$qb->setParameters($params)
			->setFirstResult($from)
			->setMaxResults($limit);
		$query = $qb->getQuery();
		
		$results = $query->getResult();
		
		return $results;
	
	}

	public function countForSent(User $user) {
		$templateIds = $this->getTemplatesIdsForUserFrom($user);
	
	
		$qb = $this->createQueryBuilder('n')
			->select('COUNT(n)')
			->join('n.revision', 'r')
			->where('n.status = :status')
			->andwhere('n.template IN (:ids)')
			->andwhere('r.deleted = 0')
			->andwhere('r.id = n.revision');
	
		$params = array(
				'status' => "pending",
				'ids' => $templateIds
		);
	
		$orCircles = $qb->expr()->orX();
		$orCircles->add('r.circles is null');
	
		$counter = 0;
		foreach ($user->getCircles() as $circle) {
			$orCircles->add('r.circles like :circle_'.$counter);
			$params['circle_'.$counter] = '%'.$circle.'%';
			++$counter;
		}
	
	
		$qb->andWhere($orCircles);
	
		$qb->setParameters($params);

		$result = $qb->getQuery()->getSingleScalarResult();
	
		return $result;
	
	}
	
	
	public function findRejectedForUser(User $user, $from, $limit, $contentTypes = null, $environments = null, $templates = null) {
	
		$qb = $this->createQueryBuilder('n')
		->select('n')
		->where('n.status = :status')
		->andwhere('n.username = :username');
		$params = array('status' => "rejected", 'username' => $user->getUsername());
		
		if($environments != null){
			$qb->andWhere('n.environment IN (:envs)');
			$params['envs'] = $environments;			
		}
		if($templates != null){
			$qb->andWhere('n.template IN (:templates)');
			$params['templates'] = $templates;
		}
		
		$qb->setParameters($params)
			->setFirstResult($from)
			->setMaxResults($limit);
		$query = $qb->getQuery();

		$results = $query->getResult();
	
		return $results;
	}
	
	/**
	 * Select notifications for logged user
	 *
	 * @param User $user
	 * @return array Notification
	 */
	public function findByPendingAndUserRoleAndCircle(User $user, $from, $limit, $contentTypes = null, $environments = null, $templates = null) {
	
		$templateIds = $this->getTemplatesIdsForUser($user, $contentTypes);
	
		$qb = $this->createQueryBuilder('n')
		->select('n')
		->where('n.status = :status')
		->andwhere('n.template IN (:ids)');
		$params = array('status' => "pending", 'ids' => $templateIds);
		
		if($environments != null){
			$qb->andWhere('n.environment IN (:envs)');
			$params['envs'] = $environments;			
		}
		if($templates != null){
			$qb->andWhere('n.template IN (:templates)');
			$params['templates'] = $templates;
		}
		
		$qb->setParameters($params)
			->setFirstResult($from)
			->setMaxResults($limit);
		$query = $qb->getQuery();

		$results = $query->getResult();
	
		return $results;
	}
	
	/**
	 * Limit template by user role and user circles
	 * 
	 * @param User $user
	 * @return array() of templateId
	 */
	 private function getTemplatesIdsForUser($user, $contentTypes = null) {
	 	$circles = $user->getCircles();
		 
	 	/** @var EntityManager $em */
		$em = $this->getEntityManager();
		
		/** @var TemplateRepository $templateRepoitory */
	 	$templateRepoitory = $em->getRepository( 'AppBundle:Template' );
	 	
	 	$results = $templateRepoitory->findByRenderOptionAndContentType('notification', $contentTypes);
	 	
	  	$templateIds = array();
	 	foreach ($results as $template) {
	 		
	 		$role = $template->getRoleTo();
	 		if ($this->authorizationChecker->isGranted($role) || $role === 'not-defined'){
	 			if(empty($template->getCirclesTo())) {
	 				$templateIds[] = $template->getId();
	 			} else {
	 				$commonCircle = array_intersect($circles, $template->getCirclesTo());
	 				if(!empty($commonCircle) || $this->authorizationChecker->isGranted('ROLE_ADMIN')) {
	 					$templateIds[] = $template->getId();
	 				}
	 			}
	 		}
	 	}
	 	return $templateIds;
	 }
	
	/**
	 * Limit template by user role and user circles
	 * 
	 * @param User $user
	 * @return array() of templateId
	 */
	 private function getTemplatesIdsForUserFrom($user, $contentTypes = null) {
	 	$circles = $user->getCircles();
		 
	 	/** @var EntityManager $em */
		$em = $this->getEntityManager();
		
		/** @var TemplateRepository $templateRepoitory */
	 	$templateRepoitory = $em->getRepository( 'AppBundle:Template' );
	 	
	 	$results = $templateRepoitory->findByRenderOptionAndContentType('notification', $contentTypes);
	 	
	  	$templateIds = array();
	  	/**@var \AppBundle\Entity\Template $template*/
	 	foreach ($results as $template) {
	 		/**@var \AppBundle\Entity\Environment $environment*/
	 		foreach ($template->getEnvironments() as $environment){
	 			if(empty($environment->getCircles()) || count(array_intersect($environment->getCircles(), $user->getCircles())) > 0){
	 				$templateIds[] = $template->getId();
	 				break;
	 			}
	 		}
	 	}
	 	return $templateIds;
	 }
	 
	 public function findReminders(\DateTime $date){
	 	
	 	$query = $this->createQueryBuilder('n');
	 	
		$query->select('n')
			->where('n.status = :status')
			->andwhere($query->expr()->lte('n.emailed', ':datePivot'))
	 		->setParameter('status','pending')
	 		->setParameter('datePivot', $date);
	 	return $query->getQuery()->getResult();
	 }
	 
	 public function findResponses(){
	 	$query = $this->createQueryBuilder('n')
			->select('n')
			->where('n.status <> :status')
			->andwhere('n.responseEmailed is NULL')
	 		->setParameters([
	 				'status' => 'pending',
	 		]);
	 	return $query->getQuery()->getResult();
	 }
}
