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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Repository\ViewRepository;
use AppBundle\Entity\View;
use AppBundle\Form\Form\ViewType;

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
		
		$objectArray = $this->get('ems.service.mapping')->generateObject ($revision->getDataField());
	
		return $this->render( 'data/revisions-data.html.twig', [
				'revision' =>  $revision,
				'revisionsSummary' => $revisionsSummary,
				'availableEnv' => $availableEnv,
				'object' => $revision->getObject($objectArray),
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
	 * @Route("/data/new-draft/{type}/{ouuid}", name="revision.new-draft"))
     * @Method({"POST"})
	 */
	public function newDraftAction($type, $ouuid, Request $request)
	{
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var ContentTypeRepository $contentTypeRepo */
		$contentTypeRepo = $em->getRepository('AppBundle:ContentType');
		$contentTypes = $contentTypeRepo->findBy([
				'name' => $type,
				'deleted' => false,
		]);
		
		if(count($contentTypes) != 1) {
			throw new NotFoundHttpException('Unknown revision');
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
	 * @Route("/data/delete/{type}/{ouuid}", name="object.delete"))
     * @Method({"POST"})
	 */
	public function deleteAction($type, $ouuid, Request $request)
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
		
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
		
		
		$revisions = $repository->findBy([
				'ouuid' => $ouuid,
				'contentType' => $contentTypes[0]
		]);
		
		
		/** @var Client $client */
		$client = $this->get('app.elasticsearch');
		
		/** @var Revision $revision */
		foreach ($revisions as $revision){
			/** @var Environment $environment */
			foreach ($revision->getEnvironments() as $environment){
				try{					
					$client->delete([
						'index' => $environment->getAlias(),
						'type' => $revision->getContentType()->getName(),
						'id' => $revision->getOuuid(),
					]);
					$this->addFlash('notice', 'The object has been unpublished from environment '.$environment->getName());					
				}
				catch(Missing404Exception $e){
					if(!$revision->getDeleted()) {
						$this->addFlash('warning', 'The object was already removed from environment '.$environment->getName());						
					}
				}
			}
			$revision->setDeleted(true);
			$em->persist($revision);
		}
		$this->addFlash('notice', count($revisions).' have been marked as deleted! ');
		$em->flush();


		return $this->redirectToRoute('data.index', [
				'contentTypeId' => $contentTypes[0]->getId()
		]);
	}
	
	
	/**
	 * 
	 * @Route("/data/draft/discard/{revisionId}", name="revision.discard"))
     * @Method({"POST"})
	 */
	public function discardRevisionAction($revisionId, Request $request)
	{
		
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
		
		//no need to generate the structure for subfields (
		$type = $data->getFieldType()->getType();
		$datFieldType = new $type;
		if($datFieldType->isContainer()){
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
     * @Method({"POST"})
	 */
	public function reindexRevisionAction($revisionId, Request $request){
		
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
			
			$objectArray = $this->get('ems.service.mapping')->generateObject ($revision->getDataField());
			/** @var \AppBundle\Entity\Environment $environment */
			foreach ($revision->getEnvironments() as $environment ){
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
		return $this->redirectToRoute('data.revisions', [
				'ouuid' => $revision->getOuuid(),
				'type' => $revision->getContentType()->getName()
		]);
		
	}

	/**
	 * @Route("/data/custom-index-view/{viewId}", name="data.customindexview"))
	 */
	public function customIndexViewAction($viewId, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var ViewRepository $viewRepository */
		$viewRepository = $em->getRepository('AppBundle:View');
		
		$view = $viewRepository->find($viewId);
		/** @var View $view **/
			
		if(!$view) {
			throw new NotFoundHttpException('View type not found');
		}
		
		/** @var \AppBundle\Form\View\ViewType $viewType */
 		$viewType = $this->get($view->getType());
		
		return $this->render( 'view/custom/'.$viewType->getBlockPrefix().'.html.twig', $viewType->getParameters($view));		
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
	 * @Route("/data/revision/{revisionId}.json", name="revision.ajaxupdate"))
     * @Method({"POST"})
	 */
	public function ajaxUpdateAction($revisionId, Request $request)
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
		
		if( $form->isValid() ){
			/** @var Revision $revision */
			$revision = $form->getData();
			$em->persist($revision);
			$em->flush();			
		}
		else{
			foreach ($form->getErrors(true, true) as $error){
				
				dump($error);
			}
		}
		
		return $this->render( 'data/ajax-revision.json.twig', [
				'revision' =>  $revision,
				'errors' => $form->getErrors(true, true),
				'form' => $form->createView(),
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
					
					$objectArray = $this->get('ems.service.mapping')->generateObject ($revision->getDataField());
					
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
			
			return $this->redirectToRoute('data.revisions', [
							'ouuid' => $revision->getOuuid(),
							'type' => $revision->getContentType()->getName(),
			]);// ('revision.edit', [ 'revisionId' => $revision->getId() ])
				
		}
// 		else{
// 			foreach ($form->getErrors(true, true) as $error){
				
// 				dump($error);
// 			}
// 			exit;
// 		}
		
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
			
		
		
		if (($form->isSubmitted() && $form->isValid()) || ! $contentType->getAskForOuuid()) {			

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
			
			if($form->isValid() || ! $contentType->getAskForOuuid()) {
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