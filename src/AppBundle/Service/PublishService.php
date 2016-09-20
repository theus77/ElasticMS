<?php

namespace AppBundle\Service;


use AppBundle\Entity\Revision;
use AppBundle\Twig\AppExtension;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Elasticsearch\Client;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use AppBundle\Entity\Environment;


class PublishService
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
	protected $em;
	protected $revRepository;
	/**@var Session $session*/
	protected $session;
	/**@var ContentTypeService $contentTypeService*/
	protected $contentTypeService;
	/**@var EnvironmentService $environmentService*/
	protected $environmentService;

	/**@var DataService $dataService*/
	protected $dataService;

	/**@var AuditService $auditService*/
	protected $auditService;
	
	/**@var UserService $userService*/
	protected $userService;
	
	
	public function __construct(
			Registry $doctrine, 
			AuthorizationCheckerInterface $authorizationChecker, 
			TokenStorageInterface $tokenStorage, 
			AppExtension $twigExtension, 
			$lockTime, 
			Client $client, 
			Mapping $mapping, 
			$instanceId,
			Session $session,
			ContentTypeService $contentTypeService,
			EnvironmentService $environmentService,
			DataService $dataService,
			AuditService $auditService,
			UserService $userService)
	{
		$this->twigExtension = $twigExtension;
		$this->doctrine = $doctrine;
		$this->authorizationChecker = $authorizationChecker;
		$this->tokenStorage = $tokenStorage;
		$this->lockTime = $lockTime;
		$this->client = $client;
		$this->mapping = $mapping;
		$this->instanceId = $instanceId;
		$this->em = $this->doctrine->getManager();
		$this->revRepository = $this->em->getRepository('AppBundle:Revision');
		$this->session = $session;
		$this->contentTypeService = $contentTypeService;
		$this->environmentService = $environmentService;
		$this->dataService = $dataService;
		$this->auditService = $auditService;
		$this->userService = $userService;
	}
	
	public function alignRevision($type, $ouuid, $envirronmentSource, $envirronmentTarget) {
		if($this->contentTypeService->getByName($type)->getEnvironment()->getName() == $envirronmentTarget){
			$this->session->getFlashBag()->add('warning', 'You can not align the default environment for '.$type.':'.$ouuid);
		}
		else{
			
			$revision = $this->revRepository->findByOuuidAndContentTypeAndEnvironnement(
					$this->contentTypeService->getByName($type),
					$ouuid, 
					$this->environmentService->getAliasByName($envirronmentSource)
			);
		
			if(count($revision) != 1){
				$this->session->getFlashBag()->add('warning', 'Missing revision in the environment '.$envirronmentSource.' for '.$type.':'.$ouuid);
			}
			else{
				$toClean = $this->revRepository->findByOuuidAndContentTypeAndEnvironnement(
					$this->contentTypeService->getByName($type),
					$ouuid,
					$this->environmentService->getAliasByName($envirronmentTarget)
				);
				
				$em = $this->doctrine->getManager();
				/** @var Revision $item */
				foreach ($toClean as $item){
					$this->dataService->lockRevision($item);
					$item->removeEnvironment($this->environmentService->getAliasByName($envirronmentTarget));
					$em->persist($item);
				}
				$revision = $revision[0];
				$this->dataService->lockRevision($revision);
				$revision->addEnvironment($this->environmentService->getAliasByName($envirronmentTarget));
				
				$status = $this->client->index([
					'id' => $revision->getOuuid(),
					'index' => $this->environmentService->getAliasByName($envirronmentTarget)->getAlias(),
					'type' => $revision->getContentType()->getName(),
					'body' => $revision->getRawData()
				]);
				
				$em->persist($revision);
				$em->flush();
				$this->session->getFlashBag()->add('notice', 'Object '.$type.':'.$ouuid.' published in '.$envirronmentTarget);
			}
			
		}
		
		
	}
	
	public function publish(Revision $revision, Environment $environment) {
		
		if( ! $this->authorizationChecker->isGranted($revision->getContentType()->getEditRole()) ){
			$this->session->getFlashBag()->add('warning', 'You are not allowed to publish the object '.$revision);
			return;
		}

		$user = $this->userService->getCurrentUser();
		if( !empty($environment->getCircles()) && !$this->authorizationChecker->isGranted('ROLE_ADMIN') && empty(array_intersect($environment->getCircles(), $user->getCircles()) )) {
			$this->session->getFlashBag()->add('warning', 'You are not allowed to publish in the environment '.$environment);
			return;
		}
		
		if($revision->getContentType()->getEnvironment() == $environment && !empty($revision->getEndTime())) {
			$this->session->getFlashBag()->add('warning', 'You can\'t publish in the default environment of the content type something else than the last revision: '.$revision);
			return;
		}

		$this->dataService->lockRevision($revision, $environment);

		$result = $this->revRepository->findByOuuidContentTypeAndEnvironnement($revision, $environment);
		

		$em = $this->doctrine->getManager();
		
		$already = false;
		/** @var Revision $item */
		foreach ($result as $item){
			if($item == $revision){
				$already = true;
				$this->session->getFlashBag()->add('warning', 'The revision '.$revision.' is already specified as published in '.$environment);
			}
			else {
				$this->dataService->lockRevision($item);
				$item->removeEnvironment($environment);
				$em->persist($item);				
			}
		}
		

		$status = $this->client->index([
				'id' => $revision->getOuuid(),
				'index' => $environment->getAlias(),
				'type' => $revision->getContentType()->getName(),
				'body' => $revision->getRawData()
		]);
		
		if(!$already) {
			$revision->addEnvironment($environment);
			$em->persist($revision);
			$em->flush();			
			$this->session->getFlashBag()->add('notice', 'Revision '.$revision.' has been published in '.$environment);
		}
		
		$this->auditService->auditLog('PublishService:publish', $revision->getRawData(), $environment->getName());
		
	}
	
	public function unpublish(Revision $revision, Environment $environment) {
		
		if( ! $this->authorizationChecker->isGranted($revision->getContentType()->getEditRole()) ){
			$this->session->getFlashBag()->add('warning', 'You are not allowed to unpublish the object '.$revision);
			return;
		}

		$user = $this->userService->getCurrentUser();
		if( !empty($environment->getCircles() && !$this->authorizationChecker->isGranted('ROLE_ADMIN') && empty(array_intersect($environment->getCircles(), $user->getCircles())) )) {
			$this->session->getFlashBag()->add('warning', 'You are not allowed to unpublish from the environment '.$environment);
			return;
		}
		
		if($revision->getContentType()->getEnvironment() == $environment) {
			$this->session->getFlashBag()->add('warning', 'You can\'t unpublish from the default environment of the content type '.$revision->getContentType());
			return;
		}

		$this->dataService->lockRevision($revision, $environment);
		
		$revision->removeEnvironment($environment);
		
		try {
			$status = $this->client->delete([
					'id' => $revision->getOuuid(),
					'index' => $environment->getAlias(),
					'type' => $revision->getContentType()->getName(),
			]);
			$this->session->getFlashBag()->add('notice', 'The object '.$revision.' has been unpublished from environment '.$environment->getName());
		}
		catch(\Exception $e){
			if(!$revision->getDeleted()) {
				$this->session->getFlashBag()->add('warning', 'The object '.$revision.' was already unpublished from environment '.$environment->getName());
			}
		}

		$em = $this->doctrine->getManager();
		$em->persist($revision);
		$em->flush();

		$this->auditService->auditLog('PublishService:unpublish', $revision->getRawData(), $environment->getName());
	}
	
	
}