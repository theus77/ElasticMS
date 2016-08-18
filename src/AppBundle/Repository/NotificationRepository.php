<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use AppBundle\Entity\Notification;
use AppBundle\Repository\TemplateRepository;
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
		->andwhere('n.templateId IN (:ids)');
		$params = array('status' => "pending", 'ids' => $templateIds);
		
		if($environments != null){
			$query->andWhere('n.environmentId IN (:envs)');
			$params['envs'] = $environments;			
		}
		if($templates != null){
			$query->andWhere('n.templateId IN (:templates)');
			$params['templates'] = $templates;
		}
		
		$query->setParameters($params);
		$result = $query->getQuery()->getSingleScalarResult();
		
		return $result;
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
		->andwhere('n.templateId IN (:ids)');
		$params = array('status' => "pending", 'ids' => $templateIds);
		
		if($environments != null){
			$qb->andWhere('n.environmentId IN (:envs)');
			$params['envs'] = $environments;			
		}
		if($templates != null){
			$qb->andWhere('n.templateId IN (:templates)');
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
}
