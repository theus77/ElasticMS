<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle;
use AppBundle\Entity\Environment;
use AppBundle\Entity\FieldType;
use AppBundle\Entity\Form\RebuildIndex;
use AppBundle\Form\DataField\ContainerType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\Select2Type;
use AppBundle\Form\Form\ContentTypeType;
use AppBundle\Form\Form\RebuildIndexType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\EnvironmentRepository;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Form\DataField\DataFieldType;
use AppBundle\Form\FieldType\FieldTypeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use AppBundle\Form\Field\ColorPickerType;
use AppBundle\Form\Form\EditEnvironmentType;

class MetaController extends AppController
{
	
	/**
	 * @Route("/meta/content-type/delete/{id}", name="contenttype.delete"))
	 */
	public function deleteContentTypeAction($id, Request $request)
	{				
		if($request->isMethod('GET') ){
			throw new BadRequestHttpException('This method doesn\'t allow GET request');
		}
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository('AppBundle:ContentType');
		
		/** @var ContentType $contentType */
		$contentType = $repository->find($id);
		
		if(! $contentType || count($contentType) != 1){
			throw $this->createNotFoundException('Content type not found');
		}
		
		$contentType->setActive(false)->setDeleted(true);
		$em->persist($contentType);
		$em->flush();
		$this->addFlash('warning', 'Content type '.$contentType->getName().' has been deleted');
		
		return $this->redirectToRoute('contenttype.list');	
	}
	
	private function addNewContentType(array $formArray, FieldType $fieldType){
		if(array_key_exists('add', $formArray)){
			dump("something to add");
			
			$child = new FieldType();
			$child->setName($formArray['ems:internal:add:field:name']);
			$child->setMany(false);
			$child->setType($formArray['ems:internal:add:field:class']);
			$child->setDeleted(false);
			$child->setParent($fieldType);
			$fieldType->addChild($child);		
			
			return true;
		}
		else{
			/** @var FieldType $child */
			foreach ($fieldType->getChildren() as $child){
				if($this->addNewContentType($formArray[$child->getName()], $child)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * @Route("/meta/content-type/edit/{id}", name="contenttype.edit"))
	 */
	public function editContentTypeAction($id, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository('AppBundle:ContentType');
		
		/** @var ContentType $contentType */
		$contentType = $repository->find($id);
		
		if(! $contentType || count($contentType) != 1){
			throw $this->createNotFoundException('Content type not found');
		}
		
		if(null == $contentType->getFieldType()) {
			$fieldType = new FieldType();
			$fieldType->setName('dataField');
			$fieldType->setMany(false);
			$fieldType->setType(ContainerType::class);
			$fieldType->setContentType($contentType);
			$fieldType->setDeleted(false);
			$fieldType->setOrderKey(0);
			$contentType->setFieldType($fieldType);
		}
		
		$inputContentType = $request->request->get('content_type');

		
		$form = $this->createForm(ContentTypeType::class, $contentType);
		
		$form->handleRequest($request);
		
		
		if ($form->isSubmitted() && $form->isValid()) {
			
			if(array_key_exists('save', $inputContentType)){
				$contentType->getFieldType()->updateOrderKeys();
				$em->persist($contentType);
				$em->flush();
				return $this->redirectToRoute('contenttype.list');				
			}
			else {
				if($this->addNewContentType($inputContentType['fieldType'], $contentType->getFieldType())) {
					$contentType->getFieldType()->updateOrderKeys();
					$em->persist($contentType);
					$em->flush();					
					return $this->redirectToRoute('contenttype.edit',[
						'id' => $id		
					]);
				}

			}
			
		}
		return $this->render( 'meta/edit-content-type.html.twig', [
				'form' => $form->createView(),
				'contentType' => $contentType,
		]);
		
	}
	
	/**
	 * @Route("/meta/content-type/list", name="contenttype.list"))
	 */
	public function listContentTypeAction(Request $request)
	{
		return $this->render( 'meta/list-content-type.html.twig');
	}
	

	/**
	 * @Route("/meta/index/edit/{id}", name="environment.edit"))
	 */
	public function editEnvironmentAction($id, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var EnvironmentRepository $repository */
		$repository = $em->getRepository('AppBundle:Environment');		/** @var Environment $environment */
		$environment = $repository->find($id);
		
		if(! $environment || count($environment) != 1){
			throw new NotFoundHttpException('Unknow environment');
		}
				
		$form = $this->createForm(EditEnvironmentType::class, $environment);
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			$em->persist($environment);
			$em->flush();
			return $this->redirectToRoute('environment.list');
		}
		
		return $this->render( 'meta/edit-environment.html.twig',[
				'environment' => $environment,
				'form' => $form->createView(),
		]);		
		
	}

	/**
	 * @Route("/meta/index/rebuild/{id}", name="index.rebuild"))
	 */
	public function rebuildIndexAction($id, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var EnvironmentRepository $repository */
		$repository = $em->getRepository('AppBundle:Environment');
		
		/** @var Environment $environment */
		$environment = $repository->find($id);
		
		if(! $environment || count($environment) != 1){
			throw new NotFoundHttpException('Unknow environment');
		}
		
		$rebuildIndex = new RebuildIndex();
		
		$form = $this->createForm(RebuildIndexType::class, $rebuildIndex);
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			/** @var  Client $client */
			$client = $this->get('app.elasticsearch');
			$indexName = $this->getParameter('instance_id').$environment->getName().$this->getFormatedTimestamp();
			
			
			/** @var \AppBundle\Repository\ContentTypeRepository $contentTypeRepository */
			$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
			$contentTypes = $contentTypeRepository->findAll();
			$mapping = [];
			/** @var ContentType $contentType */
			foreach ($contentTypes as $contentType){
				$mapping = array_merge($mapping, $contentType->generateMapping());
			}
			if(count($mapping) == 0){
				$client->indices()->create([
						'index' => $indexName,
				]);
				
			}
			else{
				$client->indices()->create([
						'index' => $indexName,
						'body' => ["mappings" => $mapping],
				]);			
			}
			
			$this->addFlash('notice', 'A new index '.$indexName.' has been created');
			
			
			
			/** @var \AppBundle\Entity\Revision $revision */
			foreach ($environment->getRevisions() as $revision) {
				$objectArray = $revision->getDataField()->getObjectArray();
				$status = $client->create([
						'index' => $this->getParameter('instance_id').$indexName,
						'id' => $revision->getOuuid(),
						'type' => $revision->getContentType()->getName(),
						'body' => $objectArray
				]);
			}
			
			$this->addFlash('notice', count($environment->getRevisions()).' objects have been reindexed');
			
			$this->switchAlias($client, $this->getParameter('instance_id').$environment->getName(), $indexName);
			$this->addFlash('notice', 'The alias <strong>'.$environment->getName().'</strong> is now pointing to '.$indexName);
			
			return $this->redirectToRoute('environment.list');
		}
		
		return $this->render( 'meta/rebuild-index.html.twig',[
				'environment' => $environment,
				'form' => $form->createView(),
		]);
		
	}
	
	/**
	 * @Route("/meta/content-type/add", name="contenttype.add-referenced"))
	 */
	public function addReferencedContentTypeAction(Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
			
		/** @var EnvironmentRepository $environmetRepository */
		$environmetRepository = $em->getRepository('AppBundle:Environment');
		
		if($request->isMethod('POST')){
			if(null != $request->get('envId') && null != $request->get('name') ){
				$defaultEnvironment = $environmetRepository->find($request->get('envId'));
				if($defaultEnvironment){
					$contentType = new ContentType();
					$contentType->setName($request->get('name'));
					$contentType->setPluralName($contentType->getName());
					$contentType->setEnvironment($defaultEnvironment);	
					
					$em->persist($contentType);
					$em->flush();
					return $this->redirectToRoute('contenttype.edit', [
						'id' => $contentType->getId()
					]);					
				}
			}
			return $this->redirectToRoute('contenttype.add-referenced');
		}

		/** @var ContentTypeRepository $contenttypeRepository */
		$contenttypeRepository = $em->getRepository('AppBundle:ContentType');
		
		$environments = $environmetRepository->findBy([
				'managed' => false,
		]);
		
		/** @var  Client $client */
		$client = $this->get('app.elasticsearch');
		
		
		$referencedContentTypes = [];
		/** @var Environment $environment */
		foreach ($environments as $environment){
			$alias = $environment->getAlias();
			$mapping = $client->indices()->getMapping([
					'index' => $alias,
			]);
			foreach ($mapping as $indexName => $index){
				foreach ($index['mappings'] as $name => $type){
					if(! $contenttypeRepository->findBy([
						'name' => $name
					])) {
						$referencedContentTypes[] = [
							'name' => $name,
							'alias' => $alias,
							'envId' => $environment->getId(),
						];
					}
				}				
			}
		}
		
		return $this->render( 'meta/add-referenced-content-type.html.twig', [
				'referencedContentTypes' => $referencedContentTypes
		]);
	}
	
	/**
	 * @Route("/meta/content-type/add", name="contenttype.add"))
	 */
	public function addContentTypeAction(Request $request)
	{
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
			
		/** @var EnvironmentRepository $environmetRepository */
		$environmetRepository = $em->getRepository('AppBundle:Environment');
		
		$environments = $environmetRepository->findBy([
			'managed' => true,
		]);
		
		$contentType = new ContentType();
		
		$form = $this->createFormBuilder($contentType)
		->add('name', IconTextType::class, [
				'icon' => 'fa fa-gear',
		])		
		->add('pluralName', TextType::class, [
				'label' => 'Plural form',
		])		
// 		->add('alias', IconTextType::class, [
// 				'label' => 'Default environment',
// 				'icon' => 'fa fa-database',
// 		])		
		->add('environment', ChoiceType::class, array(
				'label' => 'Default environment',
				'choices'  => $environments,
		        /** @var Environment $environment */
			    'choice_label' => function($environment, $key, $index) {
			        return $environment->getName();
			    },
// 			    'choice_value' => function($key) {
// 			    	dump($key);
// 			        return $key;
// 			    },
		))
		->add('save', SubmitType::class, [
				'label' => 'Create',
				'attr' => [
						'class' => 'btn btn-primary pull-right'
				]
		])
		->getForm();
		
		$form->handleRequest($request);
			
		
		
		if ($form->isSubmitted() && $form->isValid()) {
			/** @var ContentType $contentType */
			$contentType = $form->getData();

			$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
		
			$contentTypes = $contentTypeRepository->findBy([
				'name' => $contentType->getName()
			]);
			
			if(count($contentTypes) !=  0){
				$form->get ( 'name' )->addError ( new FormError( 'Another content type named ' . $contentType->getName() . ' already exists' ) );
			}
			
			if ($form->isValid()) {
				$em->persist($contentType);
				$em->flush();

				return $this->redirectToRoute('contenttype.edit', [
						'id' => $contentType->getId()
				]);
			}
			
			
		}
		
		return $this->render( 'meta/add-content-type.html.twig', [
				'form' => $form->createView()
		]);		
	}
	
	/**
	 * @Route("/meta/environment/list", name="environment.list"))
	 */
	public function listEnvironmentAction(Request $request)
	{		
		$em = $this->getDoctrine()->getManager();
		$repository = $em->getRepository('AppBundle:Environment');
		
		
		$environments = $repository->findAll();
		
		
		/** @var  Client $client */
		$client = $this->get('app.elasticsearch');
		
		$stats = $client->indices()->stats();
		
		
		$temp = [];
		$orphanIndexes = [];
		
		foreach ($client->indices()->getAliases() as $index => $aliases) {
			if(count($aliases["aliases"]) == 0 && strcmp($index{0}, '.') != 0 ){
				$orphanIndexes[] = [
						'name'=> $index,
						'total' => $stats['indices'][$index]['total']['docs']['count']
						
				];
			}
			foreach ($aliases["aliases"] as $alias => $other) {
				$temp[$alias] = $index;
			}
			
		}
		
		/** @var  Environment $environment */
		foreach ($environments as $environment) {
			$realName = ($environment->getManaged()?$this->getParameter('instance_id'):'').$environment->getName();
			
			if(isset($temp[$realName])){
				$environment->setIndex($temp[$realName]);
				$environment->setTotal($stats['indices'][$temp[$realName]]['total']['docs']['count']);
				unset($temp[$realName]);
			}
		}
// 		dump($stats);
		$unmanagedIndexes = [];
		foreach ($temp as $alias => $index){
			$unmanagedIndexes[] = [
				'index' => $index,
				'name' => $alias,
				'total' => $stats['indices'][$index]['total']['docs']['count']
			];
		}
		
		return $this->render( 'meta/list-environment.html.twig', [
				'environments' => $environments,
				'orphanIndexes' => $orphanIndexes,
				'unmanagedIndexes' => $unmanagedIndexes
		]);
	}
	
	/**
	 * @Route("/meta/index/delete/{name}", name="index.delete"))
	 */
	public function deleteIndexAction($name, Request $request)
	{
		/** @var  Client $client */
		$client = $this->get('app.elasticsearch');
		try {
			$indexes = $client->indices()->get(['index' => $name]);
			$client->indices()->delete([
					'index' => $name
			]);
		}
		catch (Missing404Exception $e){
		
		}		
		return $this->redirectToRoute('environment.list');
	}
	
	/**
	 * @Route("/meta/environment/switch/{id}", name="index.switch"))
	 */
	public function switchEnvironmentAction($id, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
			
		$environmetRepository = $em->getRepository('AppBundle:Environment');
		$environment = $environmetRepository->find($id);
		if(! $environment){
			throw new NotFoundHttpException('Unknowed environment');
		}
		/** @var Client $client */
		$client = $this->get('app.elasticsearch');
		
		$temp = [];
		
		foreach ($client->indices()->getAliases() as $index => $aliases) {

			if( strcmp($index{0}, '.') != 0 ){
				$temp[$index] = $index;
			}
			foreach ($aliases["aliases"] as $alias => $other) {
				if( strcmp($alias{0}, '.') != 0 ){
					$temp[$alias] = $alias;
				}
			}
				
		}
		
		ksort ($temp);
		
		$form = $this->createFormBuilder($environment)
			->add('save', SubmitType::class, [
					'label' => 'Create',
					'attr' => [
							'class' => 'btn btn-primary pull-right'
					]
			])
			->add('index', Select2Type::class, array(
					'choices'  => $temp,
			))
			->getForm();
		
		$form->handleRequest($request);


		if ($form->isSubmitted() && $form->isValid()) {
			/** @var Environment $environment */
			$environment = $form->getData();
			if(strcmp($environment->getName(), $environment->getIndex()) != 0) {
				
				$this->switchAlias($client, $environment->getName(), $environment->getIndex());

			}

			return $this->redirectToRoute('environment.list');
		}
				
		return $this->render( 'meta/switch-index.html.twig', [
				'form' => $form->createView(),
				'environment' => $environment
		]);
// 		return $this->redirectToRoute('environment.list');
	}
	
	private function switchAlias($client, $alias, $to){
		//TODO: atomic solution
		try {
			$indexes = $client->indices()->get(['index' => $alias]);
			// 				dump($indexes);
			$client->indices()->deleteAlias([
					'name' => $alias,
					'index' => array_keys($indexes)[0]
			]);
		}
		catch (Missing404Exception $e){
		
		}
		
		$client->indices()->putAlias([
				'index' => $to,
				'name' => $alias
		]);		
	}
	
	/**
	 * @Route("/meta/environment/create/{name}", name="environment.create"))
	 */
	public function createEnvironmentAction($name, Request $request)
	{
		if($request->isMethod('POST')) {			
			
			/** @var  Client $client */
			$client = $this->get('app.elasticsearch');
			try {
				$indexes = $client->indices()->get(['index' => $name]);
				if(strcmp($name, array_keys($indexes)[0]) != 0){
					/** @var EntityManager $em */
					$em = $this->getDoctrine()->getManager();
					
					$environmetRepository = $em->getRepository('AppBundle:Environment');
					$anotherObject = $environmetRepository->findBy([
							'name' => $name
					]);
						
					if (count ( $anotherObject ) == 0) {
						$environment = new Environment();
						$environment->setName($name);
						$environment->setManaged(false);
						
						$em->persist($environment);
						$em->flush();
					}
					
				}
			}
			catch (Missing404Exception $e){
			
			}
		}

		return $this->redirectToRoute('environment.list');
	}
	
	/**
	 * @Route("/meta/environment/delete/{id}", name="environment.delete"))
	 */
	public function deleteEnvironmentAction($id, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var  EnvironmentRepository $repository */
		$repository = $em->getRepository('AppBundle:Environment');
		/** @var  Environment $environment */
		$environment = $repository->find($id);
		if($request->isMethod('POST') && $environment){
			
			/** @var  Client $client */
			$client = $this->get('app.elasticsearch');
			if($environment->getManaged()){
				try {
					$aliasName = ($environment->getManaged()?$this->getParameter('instance_id'):'').$environment->getName();
					$indexes = $client->indices()->get(['index' => $aliasName]);
	// 				dump($indexes);
					$client->indices()->deleteAlias([
						'name' => $aliasName,
						'index' => array_keys($indexes)[0]
					]);				
				}
				catch (Missing404Exception $e){
					
				}
			}
			$em->remove($environment);
			$em->flush();
			return $this->redirectToRoute('environment.list');
		}
		throw new NotFoundHttpException('Unknow environment');
	}
	
	/**
	 * @Route("/meta/environment/add", name="environment.add"))
	 */
	public function addEnvironmentAction(Request $request)
	{
		$environment = new Environment();
		
		$form = $this->createFormBuilder($environment)
			->add('name', IconTextType::class, [
					'icon' => 'fa fa-database',
			])		
			->add('color', ColorPickerType::class, [
			])		
			->add('managed', CheckboxType::class, [
				'label' => 'Can we use this environment to publish objects?',
			])		
			->add('save', SubmitType::class, [
					'label' => 'Create',
					'attr' => [
							'class' => 'btn btn-primary pull-right'
					]
			])
			->getForm();
		
		$form->handleRequest($request);
			
		
		if ($form->isSubmitted() && $form->isValid()) {
		
			/** @var Environment $environment */
			$environment = $form->getData();

			/** @var EntityManager $em */
			$em = $this->getDoctrine()->getManager();
			
			$environmetRepository = $em->getRepository('AppBundle:Environment');
			$anotherObject = $environmetRepository->findBy([
					'name' => $environment->getName()
			]);
			
			if (count ( $anotherObject ) != 0) {
				$form->get ( 'name' )->addError ( new FormError( 'Another environment named ' . $environment->getName () . ' already exists' ) );
				// $form->addError(new FormError('Another '.$contentType->getName().' with this identifier already exists'));
			}
			else {
			
				
				/** @var  Client $client */
				$client = $this->get('app.elasticsearch');
				
				try {
					$indexes = ($client->indices()->get(['index' => $environment->getName()]));	
					
					if(strcmp($environment->getName(), array_keys($indexes)[0]) == 0){
						$form->get ( 'name' )->addError ( new FormError( 'Another index named ' . $environment->getName () . ' already exists' ) );
					}
				}
				catch (Missing404Exception $e){
					$client->indices()->create([
						'index' => $this->getParameter('instance_id').$environment->getName().$this->getFormatedTimestamp(),
						'body' => '{
		    				"aliases" : {
		        			'.json_encode($this->getParameter('instance_id').$environment->getName()).' : {}}}'
					]);					
				}
				
				
				if ($form->isValid()) {
					$em = $this->getDoctrine()->getManager();
					$em->persist($environment);
					$em->flush();
					
					return $this->redirectToRoute('environment.list');					
				}
			}
		}
		
		return $this->render( 'meta/add-environment.html.twig', [
				'form' => $form->createView()
		]);		
	}
}