<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle;
use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Entity\Revision;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Form\RevisionType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\RevisionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Elasticsearch\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Repository\EnvironmentRepository;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use AppBundle\Repository\TemplateRepository;
use AppBundle\Entity\Environment;
use AppBundle\Entity\Template;

class DataController extends AppController
{
	/**
	 * @Route("/data/draft/{contentTypeId}", name="data.draft_in_progress"))
	 */
	public function draftInProgressAction($contentTypeId, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository('AppBundle:ContentType');
		
		
		$contentType = $repository->find($contentTypeId);		
		
		if(!$contentType || count($contentType) != 1) {
			throw new NotFoundHttpException('Content type not found');
		}
		
		/** @var RevisionRepository $revisionRep */
		$revisionRep = $em->getRepository('AppBundle:Revision');
		$revisions = $revisionRep->findBy([
				'deleted' => false,
				'draft' => true,
				'endTime' => null,
				'contentType' => $contentTypeId
		]);
		
		return $this->render( 'data/draft-in-progress.html.twig', [
				'contentType' =>  $contentType,
				'revisions' => $revisions
		] );		
	}
	
	/**
	 * @Route("/data/view/{environmentName}/{type}/{ouuid}", name="data.view")
	 */
	public function viewDataAction($environmentName, $type, $ouuid, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var EnvironmentRepository $environmentRepo */
		$environmentRepo = $em->getRepository('AppBundle:Environment');
		$environments = $environmentRepo->findBy([
				'name' => $environmentName,
		]);
		if(!$environments || count($environments) != 1) {
			throw new NotFoundHttpException('Environment not found');
		}
		
		/** @var ContentTypeRepository $contentTypeRepo */
		$contentTypeRepo = $em->getRepository('AppBundle:ContentType');
		$contentTypes = $contentTypeRepo->findBy([
				'name' => $type,
				'deleted' => false,
		]);
		
		$contentType = null;
		if($contentTypes && count($contentTypes) == 1) {
			$contentType = $contentTypes[0];
		}
		
		try{
			/** @var Client $client */
			$client = $this->getElasticsearch();
			$result = $client->get([
					'index' => $environments[0]->getAlias(),
					'type' => $type,
					'id' => $ouuid,
			]);
		}
		catch(Missing404Exception $e){
			throw new NotFoundHttpException($type.' not found');			
		}
		
		return $this->render( 'data/view-data.html.twig', [
				'object' =>  $result,
				'environment' => $environments[0],
				'contentType' => $contentType,
		] );
	}
	
	/**
	 * @Route("/data/revisions/{type}/{ouuid}", name="data.revisions")
	 */
	public function revisionsDataAction($type, $ouuid, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var ContentTypeRepository $contentTypeRepo */
		$contentTypeRepo = $em->getRepository('AppBundle:ContentType');
		
		$contentTypes = $contentTypeRepo->findBy([
				'deleted' => false,
				'name' => $type,
		]);
		if(!$contentTypes || count($contentTypes) != 1) {
			throw new NotFoundHttpException('Content Type not found');
		}
		
		$repository = $em->getRepository('AppBundle:Revision');
		$revision = $repository->findBy([
				'endTime' => null,
				'ouuid' => $ouuid,
				'contentType' => $contentTypes[0],
		]);
		
	
		if(!$revision || count($revision) != 1) {
			throw new NotFoundHttpException('Revision not found');
		}
		/** @var Revision $revision */
		$revision = $revision[0];
		$revision->getDataField()->orderChildren();
		
		$revisionsSummary = $repository->getAllRevisionsSummary($ouuid);
		
		$availableEnv = $em->getRepository('AppBundle:Environment')->findAvailableEnvironements(
				$revision->getContentType()->getEnvironment());
		
	
		return $this->render( 'data/revisions-data.html.twig', [
				'revision' =>  $revision,
				'revisionsSummary' => $revisionsSummary,
				'availableEnv' => $availableEnv,
		] );
	}

	/**
	 * @Route("/data/index/{contentTypeId}/{page}.{_format}", defaults={"page": 1, "_format": "html"}, name="data.index")
	 */
	public function indexAction($contentTypeId, $_format, $page)
	{
		$repository = $this->getDoctrine()->getManager()->getRepository('AppBundle:ContentType');
		/** @var ContentType $contentType */
		$contentType = $repository->find($contentTypeId);
	
	
		if($contentType){
			$client = $this->getElasticsearch();
			$results = $client->search([
					'index' => $contentType->getEnvironment()->getAlias(),
					'version' => true,
					'size' => $this->container->getParameter('paging_size'),
					'from' => ($page-1)*$this->container->getParameter('paging_size'),
					'type' => $contentType->getName(),
			]);
				
			if( null != $contentType->getIndexTwig() ) {
				$twig = $this->getTwig();
				try {
					$template = $twig->createTemplate($contentType->getIndexTwig());
				}
				catch (\Twig_Error $e){
					$this->addFlash('error', 'There is something wrong with the index twig of '.$contentType->getName());
					$template = $twig->createTemplate('');
				}
				foreach ($results['hits']['hits'] as &$hit){
					try {
	
						$hit['_ems_twig_rendering'] = $template->render([
								'source' => $hit['_source'],
								'object' => $hit,
						]);
					}
					catch (\Twig_Error $e){
						$hit['_ems_twig_rendering'] = "Error in the template: ".$e->getMessage();
					}
				}
			}
				
			return $this->render( 'data/index.'.$_format.'.twig', [
					'results' => $results,
					'lastPage' => ceil($results['hits']['total']/$this->container->getParameter('paging_size')),
					'paginationPath' => 'data.index',
					'currentFilters' => [
							'contentTypeId' => $contentTypeId,
							'page' =>  $page,
							'_format' => $_format
					],
					'contentType' =>  $contentType
			] );
				
		}
	
		throw new NotFoundHttpException();
	
	}
	
	/**
	 *
	 * @Route("/data/new-draft/{ouuid}", name="revision.new-draft"))
	 */
	public function newDraftAction($ouuid, Request $request)
	{
		if($request->isMethod('GET') ){
			throw new BadRequestHttpException('This method doesn\'t allow GET request');
		}
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
		
		/** @var Revision $revision */
		$revisions = $repository->findBy([
				'ouuid' => $ouuid,
				'endTime' => null,
		]);
		
		if(count($revisions) != 1 || null != $revisions[0]->getEndTime()) {
			throw new NotFoundHttpException('Unknown revision');
		}
		$revision = $revisions[0];
		
		if($revision->getDraft()){
			return $this->redirectToRoute('revision.edit', [
					'revisionId' => $revision->getId()
			]);
		}
		
		$now = new \DateTime();
		
		
		$newDraft = new Revision($revision);
		$newDraft->setStartTime($now);
		
		$revision->setEndTime($now);
		
		$em->persist($revision);
		$em->persist($newDraft);
		$em->flush();
		
		return $this->redirectToRoute('revision.edit', [
				'revisionId' => $newDraft->getId()
		]);
	}
	
	
	/**
	 * 
	 * @Route("/data/draft/discard/{revisionId}", name="revision.discard"))
	 */
	public function discardRevisionAction($revisionId, Request $request)
	{
		if($request->isMethod('GET') ){
			throw new BadRequestHttpException('This method doesn\'t allow GET request');
		}
		
		/** @var EntityManager $em */ 
		$em = $this->getDoctrine()->getManager();
		
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
		/** @var Revision $revision */
		$revision = $repository->find($revisionId);
		
		if(!$revision) {
			throw $this->createNotFoundException('Revision not found');
		}
		if(!$revision->getDraft() || null != $revision->getEndTime()) {
			throw BadRequestHttpException('Only authirized on a draft');
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
				$previous->setEndTime(null);
				$em->persist($previous);
			}
			
		}
		
// 		$revision->getDataField()->detachRevision();
// 		$em->persist($revision);
// 		$em->flush();
		
		$em->remove($revision);

		$em->flush();
		
		return $this->redirectToRoute('data.draft_in_progress', [
				'contentTypeId' => $contentTypeId
		]);			
	}
	
	
	private function updateDataStructure(DataField $data, FieldType $meta){
		//no need to generate the structure for subfields (as they are virtual)
		if($data->getFieldType()->getTypeClass()->isContainer()){
			/** @var FieldType $field */
			foreach ($meta->getChildren() as $field){
				//no need to generate the structure for delete field
				if(!$field->getDeleted()){
					$child = $data->__get('ems_'.$field->getName());
					if(null == $child){
						$child = new DataField();
						$child->setFieldType($field);
						$child->setOrderKey($field->getOrderKey());
						$child->setParent($data);
						$child->setRevisionId($data->getRevisionId());
						$data->addChild($child);
					}
					$this->updateDataStructure($child, $field, null);					
				}
			}			
		}
	}
	
	/**
	 * @Route("/data/revision/re-index/{revisionId}", name="revision.reindex"))
	 */
	public function reindexRevisionAction($revisionId, Request $request){
		if($request->isMethod('GET') ){
			throw new BadRequestHttpException('This method doesn\'t allow GET request');
		}
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
		/** @var Revision $revision */
		$revision = $repository->find($revisionId);
		
		if(!$revision) {
			throw $this->createNotFoundException('Revision not found');
		}
		
		/** @var Client $client */
		$client = $this->get('app.elasticsearch');
		
	
		try{
			/** @var \AppBundle\Entity\Environment $environment */
			foreach ($revision->getEnvironments() as $environment ){
				$objectArray = $revision->getDataField()->getObjectArray();
				$status = $client->index([
						'id' => $revision->getOuuid(),
						'index' => $this->getParameter('instance_id').$environment->getName(),
						'type' => $revision->getContentType()->getName(),
						'body' => $objectArray
				]);				

				$this->addFlash('notice', 'Reindexed in '.$environment->getName());
			}
		}
		catch (\Exception $e){
			$this->addFlash('warning', 'Reindexing has failed: '.$e->getMessage());
		}
		return $this->redirectToRoute('data.view', [
				'ouuid' => $revision->getOuuid()
		]);
		
	}

	/**
	 * @Route("/data/custom-view/{environmentName}/{templateId}/{ouuid}", name="data.customview"))
	 */
	public function customViewAction($environmentName, $templateId, $ouuid, Request $request)
	{	
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var TemplateRepository $templateRepository */
		$templateRepository = $em->getRepository('AppBundle:Template');
		
		/** @var Template $template **/
		$template = $templateRepository->find($templateId);
			
		if(!$template) {
			throw new NotFoundHttpException('Template type not found');
		}
		
		/** @var EnvironmentRepository $environmentRepository */
		$environmentRepository = $em->getRepository('AppBundle:Environment');
		
		/** @var Environment $environment **/
		$environment = $environmentRepository->findBy([
			'name' => $environmentName,
		]);
			
		if(!$environment || count($environment) != 1) {
			throw new NotFoundHttpException('Environment type not found');
		}
		
		$environment = $environment[0];
		
		/** @var Client $client */
		$client = $this->getElasticsearch();
		
		$object = $client->get([
				'index' => $environment->getAlias(),
				'type' => $template->getContentType()->getName(),
				'id' => $ouuid
		]);
		
		$twig = $this->getTwig();
		
		try {
			$body = $twig->createTemplate($template->getBody());
		}
		catch (\Twig_Error $e){
			$this->addFlash('error', 'There is something wrong with the template '.$contentType->getName());
			$body = $twig->createTemplate('');
		}
		
		
		return $this->render( 'data/custom-view.html.twig', [
				'template' =>  $template,
				'object' => $object,
				'environment' => $environment,
				'contentType' => $template->getContentType(),
				'body' => $body
		] );
		
	}
	
	/**
	 * @Route("/data/draft/edit/{revisionId}", name="revision.edit"))
	 */
	public function editRevisionAction($revisionId, Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
		/** @var Revision $revision */
		$revision = $repository->find($revisionId);
		
		if(!$revision) {
			throw new NotFoundHttpException('Unknown revision');
		}

		
		if(null != $revision->getContentType()->getFieldType()){
			if(null == $revision->getDataField()){
				$data = new DataField();
				$data->setFieldType($revision->getContentType()->getFieldType());
				$data->setRevisionId($revision->getId());
				$data->setOrderKey($revision->getContentType()->getFieldType()->getOrderKey());
				$revision->setDataField($data);			
			}
			
			$this->updateDataStructure($revision->getDataField(), $revision->getContentType()->getFieldType());

		}

		$form = $this->createForm(RevisionType::class, $revision);
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && (array_key_exists('discard', $request->request->get('revision')) || $form->isValid() )) {
			
			/** @var Revision $revision */
			$revision = $form->getData();
			$em->persist($revision);
			$em->flush();
			
			if(array_key_exists('publish', $request->request->get('revision'))) {
				
				/** @var Client $client */
				$client = $this->get('app.elasticsearch'); 
				
				
				//TODO: test if draft and last version publish in
				try{
					
					$objectArray = $revision->getDataField()->getObjectArray();
					
					if( null == $revision->getOuuid() ) {
						$status = $client->create([
							'index' => $this->getParameter('instance_id').$revision->getContentType()->getEnvironment()->getName(),
							'type' => $revision->getContentType()->getName(),
							'body' => $objectArray
						]);
						
						
						
						$revision->setOuuid($status['_id']);
					}
					else {
						$status = $client->index([
								'id' => $revision->getOuuid(),
								'index' => $this->getParameter('instance_id').$revision->getContentType()->getEnvironment()->getName(),
								'type' => $revision->getContentType()->getName(),
								'body' => $objectArray
						]);
						
						
						$result = $repository->findByOuuidContentTypeAndEnvironnement($revision);
						
						
						/** @var Revision $item */
						foreach ($result as $item){
							$item->removeEnvironment($revision->getContentType()->getEnvironment());
							$em->persist($item);
						}
						
					}	
					
					$revision->addEnvironment($revision->getContentType()->getEnvironment());
					$revision->getDataField()->propagateOuuid($revision->getOuuid());
					$revision->setDraft(false);
					$revision->setModified(new \DateTime('now'));
					$em->persist($revision);
					$em->flush();
					return $this->redirectToRoute('data.revisions', [
							'ouuid' => $revision->getOuuid(),
							'type' => $revision->getContentType()->getName(),
							
					]);	
				}
				catch (\Exception $e){
					$this->addFlash('error', 'The draft has been saved but something when wrong when we tried to publish it. '.$revision->getContentType()->getName().':'.$revision->getOuuid());
					$this->addFlash('error', $e->getMessage());
				}
			}
			
			return $this->redirectToRoute('revision.edit', [
					'revisionId' => $revision->getId()
			]);
				
		}
		
		return $this->render( 'data/edit-revision.html.twig', [
				'revision' =>  $revision,
				'form' => $form->createView(),
		] );		
	}
		
	
	/**
	 * @Route("/data/add/{contentTypeId}", name="data.add"))
	 */
	public function addAction($contentTypeId, Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$repository = $em->getRepository('AppBundle:ContentType');
		/** @var ContentType $contentType */
		$contentType = $repository->find($contentTypeId);
		
		if(!$contentType){
			throw new NotFoundHttpException('Unknown content type');
		}
		
		$revision = new Revision();
		
		$form = $this->createFormBuilder($revision)
			->add('ouuid', IconTextType::class, [
					'attr' => [
						'class' => 'form-control',
						'placeholder' => 'Auto-generated if left empty'
					],
					'required' => false
			])
			->add('save', SubmitType::class, [
					'label' => 'Create '.$contentType->getName().' draft',
					'attr' => [
						'class' => 'btn btn-primary pull-right'
					]
			])
			->getForm();
			
		$form->handleRequest($request);
			
		
		
		if ($form->isSubmitted() && $form->isValid()) {			

			/** @var Revision $revision */
			$revision = $form->getData();
			

			if(null != $revision->getOuuid()){
				$revisionRepository = $em->getRepository('AppBundle:Revision');
				$anotherObject = $revisionRepository->findBy([
						'contentType' => $contentType,
						'ouuid' => $revision->getOuuid(),
						'endTime' => null
				]);
				
				if( count($anotherObject) != 0 ){
					$form->get('ouuid')->addError(new FormError('Another '.$contentType->getName().' with this identifier already exists'));
// 					$form->addError(new FormError('Another '.$contentType->getName().' with this identifier already exists'));					
				}
			}			
			
			if($form->isValid()) {
				$now = new \DateTime('now');
				$revision->setContentType($contentType);
				$revision->setDraft(true);
				$revision->setDeleted(false);
				$revision->setStartTime($now);
				$revision->setEndTime(null);
				$revision->setLockBy( $this->getUser()->getUsername() );
				$revision->setLockUntil(new \DateTime($this->getParameter('lock_time')));
				
				$em->persist($revision);
				$em->flush();
			
				//dump($revision);
				
				return $this->redirectToRoute('revision.edit', [
						'revisionId' => $revision->getId()
				]);				
			}
		}
		
		return $this->render( 'data/add.html.twig', [
				'contentType' =>  $contentType,
				'form' => $form->createView(),
		] );	
	}
	
}