<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle;
use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Entity\Revision;
use AppBundle\Form\RevisionType;
use Elasticsearch\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Form\IconTextType;
use AppBundle\Repository\ContentTypeRepository;
use Doctrine\ORM\EntityManager;
use AppBundle\Repository\RevisionRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DataController extends AppController
{
	/**
	 * @Route("/data/draft/{contentTypeId}", name="data.draft_in_progress"))
	 */
	public function viewDraftInProgressAction($contentTypeId, Request $request)
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
	 * @Route("/data/view/{ouuid}", name="data.view"))
	 */
	public function viewDataAction($ouuid, Request $request)
	{
		$em = $this->getDoctrine()->getManager();
	
		$repository = $em->getRepository('AppBundle:Revision');
	
	
		$revision = $repository->findBy([
				'endTime' => null,
				'ouuid' => $ouuid
		]);
	
		if(!$revision || count($revision) != 1) {
			throw new NotFoundHttpException('Unknown revision');
		}
	
		return $this->render( 'data/view-data.html.twig', [
				'revision' =>  $revision[0],
		] );
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
		
		if(!$revision || !$revision->getDraft() || null != $revision->getEndTime()) {
			throw new NotFoundHttpException('Unknown revision or not a draft');
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
		/** @var FieldType $field */
		foreach ($meta->getChildren() as $field){
			$child = $data->__get($field->getName());
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
	
	/**
	 * @Route("/data/draft/edit/{revisionId}", name="revision.edit"))
	 */
	public function editRevisionAction($revisionId, Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
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
				
				try{
					
					$objectArray = $revision->getDataField()->getObjectArray();
					
					if( null == $revision->getOuuid() ) {
						$status = $client->create([
							'index' => $revision->getContentType()->getAlias(),
							'type' => $revision->getContentType()->getName(),
							'body' => $objectArray
						]);
						$revision->setOuuid($status['_id']);
					}
					else {
						$status = $client->index([
								'id' => $revision->getOuuid(),
								'index' => $revision->getContentType()->getAlias(),
								'type' => $revision->getContentType()->getName(),
								'body' => $objectArray
						]);
						
					}	
					
					$revision->getDataField()->propagateOuuid($revision->getOuuid());
					$revision->setDraft(false);
					$revision->setModified(new \DateTime('now'));
					$em->persist($revision);
					$em->flush();
				}
				catch (\Exception $e){
					//TODO
					dump($e);
				}
				
				return $this->redirectToRoute('data.view', [
						'ouuid' => $revision->getOuuid()
				]);	
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