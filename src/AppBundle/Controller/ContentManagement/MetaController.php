<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle;
use AppBundle\Entity\Revision;
use AppBundle\Form\IconTextType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Environment;
use Elasticsearch\Client;
use AppBundle\Repository\EnvironmentRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Form\Select2Type;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class MetaController extends AppController
{
	/**
	 * @Route("/meta/content-type/delete/{id}", name="contenttype.delete"))
	 */
	public function deleteContentTypeAction($id, Request $request)
	{				
		//TODO
		return $this->render( 'meta/edit-content-type.html.twig');		
	}
	
	/**
	 * @Route("/meta/content-type/edit/{id}", name="contenttype.edit"))
	 */
	public function editContentTypeAction($id, Request $request)
	{
		//TODO
		return $this->render( 'meta/edit-content-type.html.twig');
		
	}
	
	/**
	 * @Route("/meta/content-type/list", name="contenttype.list"))
	 */
	public function listContentTypeAction(Request $request)
	{
				
		return $this->render( 'meta/list-content-type.html.twig');
		
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
		
		$environments = $environmetRepository->findAll();
		$temp = [];

		/** @var Environment $environment */
		foreach ($environments as $environment) {
			$temp[$environment->getName()] = $environment->getName();
		}
		
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
		->add('alias', Select2Type::class, array(
				'label' => 'Default environment',
				'choices'  => $temp,
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
			if(isset($temp[$environment->getName()])){
				$environment->setIndex($temp[$environment->getName()]);
				$environment->setTotal($stats['indices'][$temp[$environment->getName()]]['total']['docs']['count']);
				unset($temp[$environment->getName()]);
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
				
				//TODO: atomic solution
				try {
					$indexes = $client->indices()->get(['index' => $environment->getName()]);
					// 				dump($indexes);
					$client->indices()->deleteAlias([
							'name' => $environment->getName(),
							'index' => array_keys($indexes)[0]
					]);
				}
				catch (Missing404Exception $e){
				
				}
				
				$client->indices()->putAlias([
					'index' => $environment->getIndex(),
					'name' => $environment->getName()
				]);
			}

			return $this->redirectToRoute('environment.list');
		}
				
		return $this->render( 'meta/switch-index.html.twig', [
				'form' => $form->createView(),
				'environment' => $environment
		]);
// 		return $this->redirectToRoute('environment.list');
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
			try {
				$indexes = $client->indices()->get(['index' => $environment->getName()]);
// 				dump($indexes);
				$client->indices()->deleteAlias([
					'name' => $environment->getName(),
					'index' => array_keys($indexes)[0]
				]);				
			}
			catch (Missing404Exception $e){
				
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
						'index' => 'ems_'.$this->getGUID(),
						'body' => '{
		    				"aliases" : {
		        			'.json_encode($environment->getName()).' : {}}}'
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