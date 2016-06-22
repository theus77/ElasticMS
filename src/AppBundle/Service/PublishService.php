<?php

namespace AppBundle\Service;


use AppBundle\Entity\Revision;
use AppBundle\Twig\AppExtension;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Elasticsearch\Client;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


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
			DataService $dataService)
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
				$em->persist($revision);
				$em->flush();
				$this->session->getFlashBag()->add('notice', 'Object '.$type.':'.$ouuid.' published in '.$envirronmentTarget);
			}
			
		}
		
		
	}
	
	
}