<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle;
use AppBundle\Entity\DataField;
use AppBundle\Entity\Environment;
use AppBundle\Entity\FieldType;
use AppBundle\Entity\Form\Search;
use AppBundle\Entity\Revision;
use AppBundle\Entity\Template;
use AppBundle\Entity\View;
use AppBundle\Exception\LockedException;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Form\RevisionType;
use AppBundle\Form\Form\ViewType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\EnvironmentRepository;
use AppBundle\Repository\RevisionRepository;
use AppBundle\Repository\TemplateRepository;
use AppBundle\Repository\ViewRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Exception\PrivilegeException;

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
	 * @Route("/data/revisions/{type}:{ouuid}/{revisionId}", defaults={"revisionId": false} , name="data.revisions")
	 */
	public function revisionsDataAction($type, $ouuid, $revisionId, Request $request)
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
		/** @var ContentType $contentType */
		$contentType = $contentTypes[0];
		
		if(! $contentType->getEnvironment()->getManaged()){
			return $this->redirectToRoute('data.view', [
					'environmentName' => $contentType->getEnvironment()->getName(),
					'type' => $type,
					'ouuid' => $ouuid
			]);
		}
		
		
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
		
		
		if(!$revisionId) {
			$revision = $repository->findOneBy([
					'endTime' => null,
					'ouuid' => $ouuid,
					'contentType' => $contentType,
			]);			
		}
		else {
			$revision = $repository->findOneById($revisionId);			
		}
		
	
		if(!$revision || $revision->getOuuid() != $ouuid || $revision->getContentType() != $contentType) {
			throw new NotFoundHttpException('Revision not found');
		}
		
		$revision->getDataField()->orderChildren();
		
		$revisionsSummary = $repository->getAllRevisionsSummary($ouuid, $contentTypes[0]);
		
		$availableEnv = $em->getRepository('AppBundle:Environment')->findAvailableEnvironements(
				$revision->getContentType()->getEnvironment());
		
		$objectArray = $this->get('ems.service.mapping')->dataFieldToArray ($revision->getDataField());
	
		return $this->render( 'data/revisions-data.html.twig', [
				'revision' =>  $revision,
				'revisionsSummary' => $revisionsSummary,
				'availableEnv' => $availableEnv,
				'object' => $revision->getObject($objectArray),
		] );
	}
	
	public function getNewestRevision($type, $ouuid){
		return $this->get("ems.service.data")->getNewestRevision($type, $ouuid);
	}
	
	public function initNewDraft($type, $ouuid, $fromRev = null){
		return $this->get("ems.service.data")->initNewDraft($type, $ouuid, $fromRev);
	}
	

	/**
	 *
	 * @Route("/data/new-draft/{type}/{ouuid}", name="revision.new-draft"))
     * @Method({"POST"})
	 */
	public function newDraftAction($type, $ouuid, Request $request)
	{
		return $this->redirectToRoute('revision.edit', [
				'revisionId' => $this->initNewDraft($type, $ouuid)->getId()
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
			$this->lockRevision($revision, true);
			
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
				$revision->removeEnvironment($environment);
			}
			$revision->setDeleted(true);
			$em->persist($revision);
		}
		$this->addFlash('notice', count($revisions).' have been marked as deleted! ');
		$em->flush();


		return $this->redirectToRoute('elasticsearch.search', [
				'search_form[typeFacet]' => $contentTypes[0]->getName(),
				'search_form[aliasFacet]' => $contentTypes[0]->getEnvironment()->getAlias(),
		]);
	}
	
	public function discardDraft(Revision $revision){
		return $this->get("ems.service.data")->discardDraft($revision);
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
			throw BadRequestHttpException('Only authorized on a draft');
		}


		$contentTypeId = $revision->getContentType()->getId();
		$type = $revision->getContentType()->getName();
		$ouuid = $revision->getOuuid();
		
		$this->discardDraft($revision);
		
		if(null != $ouuid){
			return $this->redirectToRoute('data.revisions', [
					'type' => $type,
					'ouuid'=> $ouuid,
			]);
		}
		return $this->redirectToRoute('data.draft_in_progress', [
				'contentTypeId' => $contentTypeId
		]);			
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
		
		$this->lockRevision($revision);
		
		/** @var Client $client */
		$client = $this->get('app.elasticsearch');
		
	
		try{
			
			$objectArray = $this->get('ems.service.mapping')->dataFieldToArray ($revision->getDataField());
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
				'type' => $revision->getContentType()->getName(),
				'revisionId' => $revision->getId(),
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
	 * @Route("/data/custom-view/{environmentName}/{templateId}/{ouuid}/{_download}", defaults={"_download": false} , name="data.customview"))
	 */
	public function customViewAction($environmentName, $templateId, $ouuid, Request $request, $_download)
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
			//TODO why is the body generated and passed to the twig file while the twig file does not use it?
			//Asked by dame
			//If there is an error in the twig the user will get an 500 error page, this solution is not perfect but at least the template is tested
			$body = $twig->createTemplate($template->getBody());
		}
		catch (\Twig_Error $e){
			$this->addFlash('error', 'There is something wrong with the template body field '.$template->getName());
			$body = $twig->createTemplate('error in the template!');
		}
		
		if($_download || (strcmp($template->getRenderOption(), "export") === 0 && ! $template->getPreview()) ){
			if(null!= $template->getMimeType()){
				header('Content-Type: '.$template->getMimeType());		
			}
			
			$filename = $ouuid;
			if (null != $template->getFilename()){
				try {
					$filename = $twig->createTemplate($template->getFilename());
				} catch (\Twig_Error $e) {
					$this->addFlash('error', 'There is something wrong with the template filename field '.$template->getName());
					$filename = $twig->createTemplate('error in the template!');
				}
				
				$filename = $filename->render([
						'environment' => $environment,
						'contentType' => $template->getContentType(),
						'object' => $object,
						'source' => $object['_source'],
				]);
				$filename = preg_replace('~[\r\n]+~', '', $filename);
			}
			
			if(null!= $template->getExtension()){
				header("Content-Disposition: attachment; filename=".$filename.'.'.$template->getExtension());
			}
			
			$output = $body->render([
						'environment' => $environment,
						'contentType' => $template->getContentType(),
						'object' => $object,
						'source' => $object['_source'],
				]);
			if (null != $template->getDownloadResultUrl()){
				$ch = curl_init($output);
				curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_exec($ch);
				$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				if ($code == 200) {
					echo file_get_contents($output);
				} else {
					echo "Error ".$code." while downloading file: ".$output;
				}
				
			} else {
				echo $output;
			}
			
			exit;
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
		

		$this->lockRevision($revision);
		
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
				
			$revision->getDataField()->updateDataStructure($revision->getContentType()->getFieldType());
		
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
				
				//dump($error);
			}
		}
		
		return $this->render( 'data/ajax-revision.json.twig', [
				'revision' =>  $revision,
				'errors' => $form->getErrors(true, true),
				'form' => $form->createView(),
		] );
	}
	
	public function finalizeDraft(Revision $revision){
		return $this->get("ems.service.data")->finalizeDraft($revision);
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

		$this->lockRevision($revision);
		
		
		//Update the data structure
		if(null != $revision->getContentType()->getFieldType()){
			if(null == $revision->getDataField()){
				$data = new DataField();
				$data->setFieldType($revision->getContentType()->getFieldType());
				$data->setRevisionId($revision->getId());
				$data->setOrderKey($revision->getContentType()->getFieldType()->getOrderKey());
				$revision->setDataField($data);			
			}
			
			$revision->getDataField()->updateDataStructure($revision->getContentType()->getFieldType());

		}

		$form = $this->createForm(RevisionType::class, $revision);
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && (array_key_exists('discard', $request->request->get('revision')) || $form->isValid() )) {
			
			/** @var Revision $revision */
			$revision = $form->getData();
			$em->persist($revision);
			$em->flush();
			
			if(array_key_exists('publish', $request->request->get('revision'))) {
				
				
				
				try{
					$revision = $this->finalizeDraft($revision);

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

			if(null != $revision->getOuuid()){
				return $this->redirectToRoute('data.revisions', [
						'ouuid' => $revision->getOuuid(),
						'type' => $revision->getContentType()->getName(),
						'revisionId' => $revision->getId(),
				]);
			}
			else{
				return $this->redirectToRoute('data.draft_in_progress', [
						'contentTypeId' => $revision->getContentType()->getId(),
				]);
			}
				
		}
		
		return $this->render( 'data/edit-revision.html.twig', [
				'revision' =>  $revision,
				'form' => $form->createView(),
		] );		
	}
		
	/**
	 * deprecated should removed
	 * 
	 * @param Revision $revision
	 * @param string $publishEnv
	 * @param string $super
	 */
	private function lockRevision(Revision $revision, $publishEnv=false, $super=false){
		$this->get("ems.service.data")->lockRevision($revision, $publishEnv, $super);
	}
	
	/**
	 * @Route("/data/add/{contentType}", name="data.add"))
	 */
	public function addAction(ContentType $contentType, Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$repository = $em->getRepository('AppBundle:ContentType');
		
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
			
			if(! $this->get('app.twig_extension')->all_granted($contentType->getFieldType()->getFieldsRoles())){
				throw new PrivilegeException($revision);
			}

			

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
	
	/**
	 * @Route("/data/revisions/revert/{id}", name="revision.revert"))
	 * @Method({"POST"})
	 */
	public function revertRevisionAction(Revision $revision, Request $request)
	{
		$type = $revision->getContentType()->getName();
		$ouuid = $revision->getOuuid();
		
		$newestRevision = $this->getNewestRevision($type, $ouuid);
		if ($newestRevision->getDraft()){
			//TODO: ????
		}
		
		$revertedRevsision = $this->initNewDraft($type, $ouuid, $revision);
		$this->addFlash('notice', 'Revision '.$revision->getId().' reverted as draft');
		
		return $this->redirectToRoute('revision.edit', [
				'revisionId' => $revertedRevsision->getId()
		]);
	}
}