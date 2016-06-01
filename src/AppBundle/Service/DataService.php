<?php

namespace AppBundle\Service;


use AppBundle\Entity\Revision;
use AppBundle\Exception\LockedException;
use AppBundle\Exception\PrivilegeException;
use AppBundle\Twig\AppExtension;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Elasticsearch\Client;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use AppBundle\Repository\RevisionRepository;
use Doctrine\ORM\EntityManager;
use AppBundle\Repository\ContentTypeRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use AppBundle\Entity\DataField;


class DataService
{
	
	protected $twig;
	/**@var Registry $doctrine */
	protected $doctrine;
	/**@var AuthorizationCheckerInterface $authorizationChecker*/
	protected $authorizationChecker;
	/**@var TokenStorageInterface $tokenStorage*/
	protected $tokenStorage;
	/**@var AppExtension $twigExtension*/
	protected $twigExtension;
	protected $lockTime;
	/**@Client $client*/
	protected $client;
	/**@var Mapping $mapping*/
	protected $mapping;
	protected $instanceId;
	
	public function __construct(
			Registry $doctrine, 
			AuthorizationCheckerInterface $authorizationChecker, 
			TokenStorageInterface $tokenStorage, 
			AppExtension $twigExtension, 
			$lockTime, 
			Client $client, 
			Mapping $mapping, 
			$instanceId)
	{
		$this->twigExtension = $twigExtension;
		$this->doctrine = $doctrine;
		$this->authorizationChecker = $authorizationChecker;
		$this->tokenStorage = $tokenStorage;
		$this->lockTime = $lockTime;
		$this->client = $client;
		$this->mapping = $mapping;
		$this->instanceId = $instanceId;
	}
	
	
	public function lockRevision(Revision $revision, $publishEnv=false, $super=false, $username=null){
		if($publishEnv && !$this->authorizationChecker->isGranted('ROLE_PUBLISHER')){
			throw new PrivilegeException($revision);
		}
		
		$em = $this->doctrine->getManager();
		if($username === NULL){
			$lockerUsername = $this->tokenStorage->getToken()->getUsername();
		} else {
			$lockerUsername = $username;
		}
		$now = new \DateTime();
		if($revision->getLockBy() != $lockerUsername && $now <  $revision->getLockUntil()) {
			throw new LockedException($revision);
		}
		
		if(!$username && !$this->twigExtension->one_granted($revision->getContentType()->getFieldType()->getFieldsRoles(), $super)) {
			throw new PrivilegeException($revision);
		}
		//TODO: test circles
		
		$revision->setLockBy($lockerUsername);
		$revision->setLockUntil(new \DateTime($this->lockTime));
		$revision->setStartTime($now);
		
		$em->persist($revision);
		$em->flush();
	}
	
	
	public function finalizeDraft(Revision $revision, $username=null){
		$this->lockRevision($revision, false, false, $username);
	
		$em = $this->doctrine->getManager();
	
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
	
		//TODO: test if draft and last version publish in
			
		$objectArray = $this->mapping->dataFieldToArray ($revision->getDataField());
			
		if( null == $revision->getOuuid() ) {
			$status = $this->client->create([
					'index' => $this->instanceId.$revision->getContentType()->getEnvironment()->getName(),
					'type' => $revision->getContentType()->getName(),
					'body' => $objectArray
			]);
	
	
	
			$revision->setOuuid($status['_id']);
		}
		else {
			$status = $this->client->index([
					'id' => $revision->getOuuid(),
					'index' => $this->instanceId.$revision->getContentType()->getEnvironment()->getName(),
					'type' => $revision->getContentType()->getName(),
					'body' => $objectArray
			]);
	
	
			$result = $repository->findByOuuidContentTypeAndEnvironnement($revision);
	
	
			/** @var Revision $item */
			foreach ($result as $item){
				$this->lockRevision($item, false, false, $username);
				$item->removeEnvironment($revision->getContentType()->getEnvironment());
				$em->persist($item);
			}
		}
			
		$revision->addEnvironment($revision->getContentType()->getEnvironment());
		$revision->getDataField()->propagateOuuid($revision->getOuuid());
		$revision->setDraft(false);
		$em->persist($revision);
		$em->flush();
	
		return $revision;
	}
	

	public function getNewestRevision($type, $ouuid){
		/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
	
		/** @var ContentTypeRepository $contentTypeRepo */
		$contentTypeRepo = $em->getRepository('AppBundle:ContentType');
		$contentTypes = $contentTypeRepo->findBy([
				'name' => $type,
				'deleted' => false,
		]);
	
		if(count($contentTypes) != 1) {
			throw new NotFoundHttpException('Unknown content type');
		}
		$contentType = $contentTypes[0];
	
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
	
		/** @var Revision $revision */
		$revisions = $repository->findBy([
				'ouuid' => $ouuid,
				'endTime' => null,
				'contentType' => $contentType,
		]);
	
		if(count($revisions) != 1 || null != $revisions[0]->getEndTime()) {
			throw new NotFoundHttpException('Unknown revision');
		}
		$revision = $revisions[0];
	
		return $revision;
	}
	

	public function initNewDraft($type, $ouuid, $fromRev = null, $username = NULL){
	
		/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
		
		/** @var ContentTypeRepository $contentTypeRepo */
		$contentTypeRepo = $em->getRepository('AppBundle:ContentType');
		$contentType = $contentTypeRepo->findOneBy([
				'name' => $type,
				'deleted' => false,
		]);

		if(!$contentType){
			throw new NotFoundHttpException('ContentType '.$type.' Not found');
		}
		
		try{
			$revision = $this->getNewestRevision($type, $ouuid);
			$revision->getDataField()->propagateOuuid($revision->getOuuid());			
		}
		catch(NotFoundHttpException $e){
			$revision = new Revision();
			$revision->setDraft(true);
			$revision->setOuuid($ouuid);
			$revision->setContentType($contentType);
		}
		
		$this->lockRevision($revision, false, false, $username);
	
		if(! $revision->getDraft()){
			$now = new \DateTime();
	
			if ($fromRev){
				$newDraft = new Revision($fromRev);
			} else {
				$newDraft = new Revision($revision);
			}
				
			$newDraft->setStartTime($now);
			$revision->setEndTime($now);
	
			$this->lockRevision($newDraft, false, false, $username);
	
			$em->persist($revision);
			$em->persist($newDraft);
			$em->flush();
			return $newDraft;
		}
		return $revision;
	
	}
	

	public function discardDraft(Revision $revision){
		$this->lockRevision($revision);
	
		/** @var EntityManager $em */
		$em = $this->doctrine->getManager();
	
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
	
		if(!$revision) {
			throw new NotFoundHttpException('Revision not found');
		}
		if(!$revision->getDraft() || null != $revision->getEndTime()) {
			throw new BadRequestHttpException('Only authorized on a draft');
		}
	
		$contentTypeId = $revision->getContentType()->getId();
	
		if(null != $revision->getOuuid()){
			/** @var QueryBuilder $qb */
			$qb = $repository->createQueryBuilder('t')
			->where('t.ouuid = :ouuid')
			->setParameter('ouuid', $revision->getOuuid())
			->andWhere('t.id <> :id')
			->setParameter('id', $revision->getId())
			->orderBy('t.startTime', 'desc')
			->setMaxResults(1);
			$query = $qb->getQuery();
	
	
			$result = $query->getResult();
			if(count($result) == 1){
				/** @var Revision $previous */
				$previous = $result[0];
				$this->lockRevision($previous);
				$previous->setEndTime(null);
				$em->persist($previous);
			}
	
		}
	
		$em->remove($revision);
	
		$em->flush();
	}
}