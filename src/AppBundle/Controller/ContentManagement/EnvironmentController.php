<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle;
use AppBundle\Entity\Environment;
use AppBundle\Entity\Form\RebuildIndex;
use AppBundle\Entity\Revision;
use AppBundle\Form\Field\ColorPickerType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Form\Form\EditEnvironmentType;
use AppBundle\Form\Form\RebuildIndexType;
use AppBundle\Repository\ContentTypeRepository;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class EnvironmentController extends AppController {
	
	/**
	 * Attach a external index as a new referenced environment
	 *
	 * @param string $name
	 *        	alias name
	 * @param Request $request
	 * 
	 * @Route("/environment/attach/{name}", name="environment.attach"))
     * @Method({"POST"})
	 */
	public function attachAction($name, Request $request) {
		/** @var  Client $client */
		$client = $this->get ( 'app.elasticsearch' );
		try {
			$indexes = $client->indices ()->get ( [ 
					'index' => $name 
			] );
			if (strcmp ( $name, array_keys ( $indexes ) [0] ) != 0) {
				/** @var EntityManager $em */
				$em = $this->getDoctrine ()->getManager ();
				
				$environmetRepository = $em->getRepository ( 'AppBundle:Environment' );
				$anotherObject = $environmetRepository->findBy ( [ 
						'name' => $name 
				] );
				
				if (count ( $anotherObject ) == 0) {
					$environment = new Environment ();
					$environment->setName ( $name );
					$environment->setAlias ( $name );
					$environment->setManaged ( false );
					
					$em->persist ( $environment );
					$em->flush ();
					
					$this->addFlash ( 'notice', 'Alias ' . $name . ' has been attached.' );
					
					return $this->redirectToRoute ( 'environment.edit', [ 
							'id' => $environment->getId () 
					] );
				}
			}
		} catch ( Missing404Exception $e ) {
			$this->addFlash ( 'error', 'Something went wrong with Elasticsearch: ' . $e->getMessage () . '!' );
		}
		
		return $this->redirectToRoute ( 'environment.index' );
	}
	
	/**
	 * Try to remove an evironment if it is empty form an eMS perspective.
	 * If it's managed environment the Elasticsearch alias will be also removed.
	 *
	 * @param integer $id        	
	 * @param Request $request
	 * 
	 * @Route("/environment/remove/{id}", name="environment.remove"))
     * @Method({"POST"})
	 *        	
	 */
	public function removeAction($id, Request $request) {
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var  EnvironmentRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:Environment' );
		/** @var  Environment $environment */
		$environment = $repository->find ( $id );
			
		/** @var  Client $client */
		$client = $this->get ( 'app.elasticsearch' );
		if ($environment->getManaged ()) {
			try {
				$indexes = $client->indices ()->get ( [ 
						'index' => $environment->getAlias () 
				] );
				$client->indices ()->deleteAlias ( [ 
						'name' => $environment->getAlias (),
						'index' => array_keys ( $indexes ) [0] 
				] );
			} catch ( Missing404Exception $e ) {
				$this->addFlash ( 'warning', 'Alias ' . $environment->getAlias () . ' not found in Elasticsearch' );
			}
		}
		if ($environment->getRevisions ()->count () != 0) {
			$this->addFlash ( 'error', 'The environement ' . $environment->getName () . ' is not empty.' );
		} else {
			$this->addFlash ( 'notice', 'The environment '.$environment->getName().' has been removed' );
			$em->remove ( $environment );
			$em->flush ();
		}
		
		return $this->redirectToRoute ( 'environment.index' );
	}
	
	/**
	 * Add a new environement
	 *
	 * @param Request $request
	 *        	@Route("/environment/add", name="environment.add"))
	 */
	public function addAction(Request $request) {
		$environment = new Environment ();
		
		$form = $this->createFormBuilder ( $environment )->add ( 'name', IconTextType::class, [ 
				'icon' => 'fa fa-database',
				'required' => false 
		] )->add ( 'color', ColorPickerType::class, [ 
				'required' => false 
		] )->add ( 'save', SubmitEmsType::class, [ 
				'label' => 'Create',
				'icon' => 'fa fa-plus',
				'attr' => [ 
						'class' => 'btn btn-primary pull-right' 
				] 
		] )->getForm ();
		
		$form->handleRequest ( $request );
		
		if ($form->isSubmitted () && $form->isValid ()) {
			
			/** @var Environment $environment */
			$environment = $form->getData ();
			
			/** @var EntityManager $em */
			$em = $this->getDoctrine ()->getManager ();
			
			$environmetRepository = $em->getRepository ( 'AppBundle:Environment' );
			$anotherObject = $environmetRepository->findBy ( [ 
					'name' => $environment->getName () 
			] );
			
			if (count ( $anotherObject ) != 0) {
				//TODO: test name format
				$form->get ( 'name' )->addError ( new FormError ( 'Another environment named ' . $environment->getName () . ' already exists' ) );
			} else {
				
				/** @var  Client $client */
				$client = $this->get ( 'app.elasticsearch' );
				
				$environment->setAlias ( $this->getParameter ( 'instance_id' ) . $environment->getName () );
				$environment->setManaged ( true );
				try {
					$indexes = $client->indices ()->get ( [ 
							'index' => $environment->getAlias () 
					] );
					$form->get ( 'name' )->addError ( new FormError ( 'Another index named ' . $environment->getName () . ' already exists' ) );
					
				} catch ( Missing404Exception $e ) {
					/** @var \AppBundle\Repository\ContentTypeRepository $contentTypeRepository */
					$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
					$contentTypes = $contentTypeRepository->findAll();
					
					$mapping = [];
					/** @var ContentType $contentType */
					foreach ($contentTypes as $contentType){
						if($contentType->getEnvironment()->getManaged()){
							$mapping = array_merge($mapping, $contentType->generateMapping());
						}
					}
					
					
					
					$body = '{'.
							(count($mapping)>0?'"mappings" : {'.json_encode ( $mapping ).'},':'').
		    				'"aliases" : {
		        			' . json_encode ( $environment->getAlias () ) . ' : {}}}';
					
					$client->indices ()->create ( [ 
							'index' => $environment->getAlias () . $this->getFormatedTimestamp (),
							'body' =>  $body
					] );
					if ($form->isValid ()) {
						$em = $this->getDoctrine ()->getManager ();
						$em->persist ( $environment );
						$em->flush ();
						$this->addFlash('notice', 'A new environement '.$environment->getName().' has been created');
						
						
						return $this->redirectToRoute ( 'environment.index' );
					}
				}
				
			}
		}
		
		return $this->render ( 'environment/add.html.twig', [ 
				'form' => $form->createView () 
		] );
	}
	




	/**
	 * Edit environement (name and color). It's not allowed to update the elasticsearch alias.
	 * @param unknown $id
	 * @param Request $request
	 * @throws NotFoundHttpException
	 * @Route("/environment/edit/{id}", name="environment.edit"))
	 */
	public function editAction($id, Request $request)
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
	
		$form = $this->createForm(EditEnvironmentType::class, $environment);
	
		$form->handleRequest($request);
	
		if ($form->isSubmitted() && $form->isValid()) {
			$em->persist($environment);
			$em->flush();
			$this->addFlash('notice', 'Environment '.$environment->getName().' has been updated');
			return $this->redirectToRoute('environment.index');
		}
	
		return $this->render( 'environment/edit.html.twig',[
				'environment' => $environment,
				'form' => $form->createView(),
		]);
	
	}


	/**
	 * View environement details (especially the mapping information).
	 * @param integer $id 
	 * @param Request $request
	 * @throws NotFoundHttpException
	 * @Route("/environment/{id}", name="environment.view"))
	 */
	public function viewAction($id, Request $request)
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

		/** @var  Client $client */
		$client = $this->get('app.elasticsearch');
		
		/** @var ContentTypeRepository $contentTypeRep */
		$contentTypeRep = $em->getRepository('AppBundle:ContentType');
		
		
		try{
			$info = $client->indices()->getMapping([
					'index' => $environment->getAlias(),
			]);		
		}
		catch (Missing404Exception $e){
			$this->addFlash('error', 'Elasticsearch alias '.$environment->getAlias().' is missing. Consider to rebuild the indexes.');
			$info = false;
		}
	
		return $this->render( 'environment/view.html.twig',[
				'environment' => $environment,
				'info' => $info,
		]);
	
	}
	
	/**
	 * A new index will be created and all object's revision defined in this environnement will be published.
	 * Once it's done the environment alias is updarted.
	 * 
	 * @param Environment $environment
	 */
	private function reindexAllInNewIndex(Environment $environment){
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var  Client $client */
		$client = $this->get('app.elasticsearch');
		$indexName = $environment->getAlias().$this->getFormatedTimestamp();
			
			
		/** @var \AppBundle\Repository\ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
		$contentTypes = $contentTypeRepository->findAll();
		/** @var ContentType $contentType */
		

		$client->indices()->create([
				'index' => $indexName,
				'body' => ContentType::getIndexAnalysisConfiguration(),
		]);
		$this->addFlash('notice', 'A new index '.$indexName.' has been created');
		
		$mapping = [];
		
		/** @var ContentType $contentType */
		foreach ($contentTypes as $contentType){
			if($contentType->getEnvironment()->getManaged()){
				$out = $client->indices ()->putMapping ( [
						'index' => $indexName,
						'type' => $contentType->getName (),
						'body' => $contentType->generateMapping ()
				] );
				$this->addFlash('notice', 'A new mapping for '.$contentType->getName ().' has been defined');
			}
		}
			
		$this->reindexAll($environment, $indexName);
	
		$this->switchAlias($client, $environment->getAlias(), $indexName);
		$this->addFlash('notice', 'The alias <strong>'.$environment->getName().'</strong> is now pointing to '.$indexName);
	
	}
	
	/**
	 * Go throw all objects defined for a specfic environement and republish them into the index correspond to the environment.
	 * 
	 * @param Environment $environment
	 * @param unknown $alias
	 */
	private function reindexAll(Environment $environment, $alias){
		/** @var  Client $client */
		$client = $this->get('app.elasticsearch');
		/** @var \AppBundle\Entity\Revision $revision */
		foreach ($environment->getRevisions() as $revision) {
			$objectArray = $revision->getDataField()->getObjectArray();
			$status = $client->index([
					'index' => $alias,
					'id' => $revision->getOuuid(),
					'type' => $revision->getContentType()->getName(),
					'body' => $objectArray
			]);
		}
			
		$this->addFlash('notice', count($environment->getRevisions()).' objects have been reindexed in '.$alias);
	}
	
	
	/**
	 * Rebuils a environement in elasticsearch in a new index or not (depending the rebuild option)
	 * 
	 * @param integer $id
	 * @param Request $request
	 * @throws NotFoundHttpException
	 * @Route("/environment/rebuild/{id}", name="environment.rebuild"))
	 */
	public function rebuild($id, Request $request)
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
				
			$option = $rebuildIndex->getOption();
				
			switch ($option){
				case "newIndex":
					$this->reindexAllInNewIndex($environment);
					break;
				case "sameIndex":
					$this->reindexAll($environment, $environment->getAlias());
					break;
				default:
					$this->addFlash('warning', 'Unknow rebuild option: '.$option.'.');
			}
			return $this->redirectToRoute('environment.index');
		}
	
		return $this->render( 'environment/rebuild.html.twig',[
				'environment' => $environment,
				'form' => $form->createView(),
		]);
	
	}

	/**
	 * List all environments, orphean indexes, unmanaged aliases and referenced environments
	 * 
	 * @param Request $request
	 * @Route("/environment", name="environment.index"))
	 */
	public function indexAction(Request $request)
	{
		try{
			/** @var EntityManager $em */
			$em = $this->getDoctrine()->getManager();
			/** @var EnvironmentRepository $repository */
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
				if(isset($temp[$environment->getAlias()])){
					$environment->setIndex($temp[$environment->getAlias()]);
					$environment->setTotal($stats['indices'][$temp[$environment->getAlias()]]['total']['docs']['count']);
					unset($temp[$environment->getAlias()]);
				}
			}
			$unmanagedIndexes = [];
			foreach ($temp as $alias => $index){
				$unmanagedIndexes[] = [
						'index' => $index,
						'name' => $alias,
						'total' => $stats['indices'][$index]['total']['docs']['count']
				];
			}
		
			return $this->render( 'environment/index.html.twig', [
					'environments' => $environments,
					'orphanIndexes' => $orphanIndexes,
					'unmanagedIndexes' => $unmanagedIndexes
			]);
		}
		catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e){
			return $this->redirectToRoute('elasticsearch.status');
		}
	}
	
	/**
	 * Update the alias of an environement to a new index
	 * 
	 * @param Client $client
	 * @param string $alias
	 * @param string $to
	 */
	private function switchAlias(Client $client, $alias, $to){
		try{		
			$result = $client->indices()->getAlias(['name' => $alias]);
			$index = array_keys ( $result ) [0];
			$params ['body'] = [ 
					'actions' => [ 
							[ 
									'remove' => [ 
											'index' => $index,
											'alias' => $alias 
									],
									'add' => [ 
											'index' => $to,
											'alias' => $alias 
									] 
							] 
					] 
			];
			$client->indices ()->updateAliases ( $params );
		}
		catch(Missing404Exception $e){
			$this->addFlash ( 'warning', 'Alias '.$alias.' not found' );
			$client->indices()->putAlias([
					'index' => $to,
					'name' => $alias
			]);
		}

	}
	
}