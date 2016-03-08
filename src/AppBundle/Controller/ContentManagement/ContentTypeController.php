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
use Elasticsearch\Common\Exceptions\BadRequest400Exception;

/**
 * Operations on content types such as CRUD but alose rebuild index, .
 *
 *
 *
 *
 *
 * ..
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class ContentTypeController extends AppController {
	/**
	 * Logically delete a content type.
	 * GET calls are n't supported.
	 *
	 * @param integer $id
	 *        	identifier of the content type to delete
	 * @param Request $request
	 *        	@Route("/content-type/remove/{id}", name="contenttype.remove"))
	 */
	public function removeAction($id, Request $request) {
		if ($request->isMethod ( 'GET' )) {
			$this->addFlash ( 'error', 'You are\'t not able to delete a Content Type wit a GET!' );
		} else {
			/** @var EntityManager $em */
			$em = $this->getDoctrine ()->getManager ();
			/** @var ContentTypeRepository $repository */
			$repository = $em->getRepository ( 'AppBundle:ContentType' );
			
			/** @var ContentType $contentType */
			$contentType = $repository->find ( $id );
			
			if ($contentType && count ( $contentType ) == 1) {
				$contentType->setActive ( false )->setDeleted ( true );
				$em->persist ( $contentType );
				$em->flush ();
				$this->addFlash ( 'warning', 'Content type ' . $contentType->getName () . ' has been deleted' );
			} else {
				
				$this->addFlash ( 'warning', 'Content type ' . $id . ' not found deleted' );
			}
		}
		return $this->redirectToRoute ( 'contenttype.list' );
	}
	
	/**
	 * Activate (make it available for authors) a content type.
	 * Checks that the content isn't dirty (as far as eMS knows the Mapping in Elasticsearch is up-to-date).
	 *
	 * @param integer $id        	
	 * @param Request $request
	 *        	@Route("/content-type/activate/{id}", name="contenttype.activate"))
	 */
	public function activateAction($id, Request $request) {
		if ($request->isMethod ( 'GET' )) {
			$this->addFlash ( 'error', 'You can\'t activate a content type with a GET!' );
			return $this->redirectToRoute ( 'contenttype.list' );
		}
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:ContentType' );
		
		/** @var ContentType $contentType */
		$contentType = $repository->find ( $id );
		
		if (! $contentType || count ( $contentType ) != 1) {
			$this->addFlash ( 'warning', 'Content type not found!' );
			return $this->redirectToRoute ( 'contenttype.list' );
		}
		
		if ($contentType->getDirty ()) {
			$this->addFlash ( 'warning', 'Content type "' . $contentType->getName () . '" is dirty (its mapping migth be out-of date).
					Try to update its mapping.' );
			return $this->redirectToRoute ( 'contenttype.list' );
		}
		
		$contentType->setActive ( true );
		$em->persist ( $contentType );
		$em->flush ();
		return $this->redirectToRoute ( 'contenttype.index' );
	}
	
	/**
	 * Try to update the Elasticsearch mapping for a specific content type
	 *
	 * @param integer $id        	
	 * @param Request $request        	
	 * @throws BadRequestHttpException @Route("/content-type/refresh-mapping/{id}", name="contenttype.refreshmapping"))
	 */
	public function refreshMappingAction($id, Request $request) {
		if ($request->isMethod ( 'GET' )) {
			$this->addFlash ( 'error', 'You can\'t refresh a content type\'s mapping with a GET!' );
		}
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:ContentType' );
		
		/** @var ContentType $contentType */
		$contentType = $repository->find ( $id );
		
		if (! $contentType || count ( $contentType ) != 1) {
			$this->addFlash ( 'warning', 'Content type not found!' );
			return $this->redirectToRoute ( 'contenttype.list' );
		}
		
		/** @var EnvironmentRepository $envRep */
		$envRep = $em->getRepository ( 'AppBundle:Environment' );
		
		$envs = array_reduce ( $envRep->findManagedIndexes (), function ($envs, $item) {
			if (isset ( $envs )) {
				$envs .= ',' . $item ['alias'];
			} else {
				$envs = $item ['alias'];
			}
			return $envs;
		} );
		
		try {
			/** @var  Client $client */
			$client = $this->get ( 'app.elasticsearch' );
			
			
			$out = $client->indices ()->putMapping ( [ 
					'index' => $envs,
					'type' => $contentType->getName (),
					'body' => $contentType->generateMapping ()
			] );
			
			if (isset ( $out ['acknowledged'] ) && $out ['acknowledged']) {
				$this->addFlash ( 'notice', 'Mappings successfully updated' );
			} else {
				$this->addFlash ( 'warning', '<p><strong>Something went wrong. Try again</strong></p>
						<p>Message from Elasticsearch: ' . print_r ( $out, true ) . '</p>' );
			}
			
			$contentType->setDirty ( false );
			$em->persist ( $contentType );
			$em->flush ();
		} catch ( BadRequest400Exception $e ) {
			$this->addFlash ( 'error', '<p><strong>You should try to rebuild the indexes</strong></p>
					<p>Message from Elasticsearch: ' . $e->getMessage () . '</p>' );
		}
		
		return $this->redirectToRoute ( 'contenttype.index' );
	}
	
	/**
	 * Initiate a new content type as a draft
	 *
	 * @param Request $request
	 *        	@Route("/content-type/add", name="contenttype.add"))
	 */
	public function addAction(Request $request) {
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		
		/** @var EnvironmentRepository $environmetRepository */
		$environmetRepository = $em->getRepository ( 'AppBundle:Environment' );
		
		$environments = $environmetRepository->findBy ( [ 
				'managed' => true 
		] );
		
		$contentType = new ContentType ();
		
		$form = $this->createFormBuilder ( $contentType )->add ( 'name', IconTextType::class, [ 
				'icon' => 'fa fa-gear' 
		] )->add ( 'pluralName', TextType::class, [ 
				'label' => 'Plural form' 
		] )->add ( 'environment', ChoiceType::class, array (
				'label' => 'Default environment',
				'choices' => $environments,
				/** @var Environment $environment */
				'choice_label' => function ($environment, $key, $index) {
					return $environment->getName ();
				} 
		) )->add ( 'save', SubmitType::class, [ 
				'label' => 'Create',
				'attr' => [ 
						'class' => 'btn btn-primary pull-right' 
				] 
		] )->getForm ();
		
		$form->handleRequest ( $request );
		
		if ($form->isSubmitted () && $form->isValid ()) {
			/** @var ContentType $contentType */
			$contentType = $form->getData ();
			
			$contentTypeRepository = $em->getRepository ( 'AppBundle:ContentType' );
			
			$contentTypes = $contentTypeRepository->findBy ( [ 
					'name' => $contentType->getName () 
			] );
			
			if (count ( $contentTypes ) != 0) {
				$form->get ( 'name' )->addError ( new FormError ( 'Another content type named ' . $contentType->getName () . ' already exists' ) );
			}
			
			if ($form->isValid ()) {
				$em->persist ( $contentType );
				$em->flush ();
				
				$this->addFlash ( 'notice', 'A new content type ' . $contentType->getName () . ' has been created' );
				
				return $this->redirectToRoute ( 'contenttype.edit', [ 
						'id' => $contentType->getId () 
				] );
			}
		}
		
		return $this->render ( 'contenttype/add.html.twig', [ 
				'form' => $form->createView () 
		] );
	}
	
	/**
	 * List all content types
	 *
	 * @param Request $request
	 *        	@Route("/content-type", name="contenttype.index"))
	 */
	public function indexAction(Request $request) {
		return $this->render ( 'contenttype/index.html.twig' );
	}
	
	/**
	 * List all unreferenced content types (from external sources)
	 *
	 * @param Request $request
	 *        	@Route("/content-type/unreferenced", name="contenttype.unreferenced"))
	 */
	public function unreferencedAction(Request $request) {
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		
		/** @var EnvironmentRepository $environmetRepository */
		$environmetRepository = $em->getRepository ( 'AppBundle:Environment' );
		
		if ($request->isMethod ( 'POST' )) {
			if (null != $request->get ( 'envId' ) && null != $request->get ( 'name' )) {
				$defaultEnvironment = $environmetRepository->find ( $request->get ( 'envId' ) );
				if ($defaultEnvironment) {
					$contentType = new ContentType ();
					$contentType->setName ( $request->get ( 'name' ) );
					$contentType->setPluralName ( $contentType->getName () );
					$contentType->setEnvironment ( $defaultEnvironment );
					$contentType->setActive ( true );
					$contentType->setDirty ( false );
					
					$em->persist ( $contentType );
					$em->flush ();
					$this->addFlash ( 'notice', 'The content type ' . $contentType->getName () . ' is now referenced' );
					return $this->redirectToRoute ( 'contenttype.edit', [ 
							'id' => $contentType->getId () 
					] );
				}
			}
			$this->addFlash ( 'warning', 'Unreferenced content type not found.' );
			return $this->redirectToRoute ( 'contenttype.unreferenced' );
		}
		
		/** @var ContentTypeRepository $contenttypeRepository */
		$contenttypeRepository = $em->getRepository ( 'AppBundle:ContentType' );
		
		$environments = $environmetRepository->findBy ( [ 
				'managed' => false 
		] );
		
		/** @var  Client $client */
		$client = $this->get ( 'app.elasticsearch' );
		
		$referencedContentTypes = [ ];
		/** @var Environment $environment */
		foreach ( $environments as $environment ) {
			$alias = $environment->getAlias ();
			$mapping = $client->indices ()->getMapping ( [ 
					'index' => $alias 
			] );
			foreach ( $mapping as $indexName => $index ) {
				foreach ( $index ['mappings'] as $name => $type ) {
					$already = $contenttypeRepository->findBy ( [ 
							'name' => $name 
					] );
					if (! $already || $already [0]->getDeleted ()) {
						$referencedContentTypes [] = [ 
								'name' => $name,
								'alias' => $alias,
								'envId' => $environment->getId () 
						];
					}
				}
			}
		}
		
		return $this->render ( 'meta/add-referenced-content-type.html.twig', [ 
				'referencedContentTypes' => $referencedContentTypes 
		] );
	}
	
	/**
	 * Try to find (recursively) if there is a new field to add to the content type
	 * 
	 * @param array $formArray        	
	 * @param FieldType $fieldType        	
	 */
	private function addNewContentType(array $formArray, FieldType $fieldType) {
		if (array_key_exists ( 'add', $formArray )) {
			
			$child = new FieldType ();
			$child->setName ( $formArray ['ems:internal:add:field:name'] );
			$child->setType ( $formArray ['ems:internal:add:field:class'] );
			$child->setParent ( $fieldType );
			$fieldType->addChild ( $child );
			$this->addFlash('notice', 'The field '.$fieldType->getName().' has been prepared to be added');
			return true;
		} else {
			/** @var FieldType $child */
			foreach ( $fieldType->getChildren () as $child ) {
				if (! $child->getDeleted () && $this->addNewContentType ( $formArray [$child->getName ()], $child )) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Try to find (recursively) if there is a field to remove from the content type
	 * 
	 * @param array $formArray
	 * @param FieldType $fieldType
	 */
	private function removeContentType(array $formArray, FieldType $fieldType){
		if(array_key_exists('remove', $formArray)){
			$fieldType->setDeleted(true);
			$this->addFlash('notice', 'The field '.$fieldType->getName().' has been prepared to be removed');
			return true;
		}
		else{
			/** @var FieldType $child */
			foreach ($fieldType->getChildren() as $child){
				if(!$child->getDeleted() && $this->removeContentType($formArray[$child->getName()], $child)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Try to find (recursively) if there is a container where subfields must be reordered in the content type
	 *
	 * @param array $formArray
	 * @param FieldType $fieldType
	 */	
	private function reorderContentType(array $formArray, FieldType $fieldType){
		if(array_key_exists('reorder', $formArray)){
			$keys = array_keys($formArray);
			/** @var FieldType $child */
			foreach ($fieldType->getChildren() as $child){
				if(! $child->getDeleted() ){
					$child->setOrderKey(array_search($child->getName(), $keys));
				}
			}

			$this->addFlash('notice', 'Subfields in '.$fieldType->getName().' has been prepared to be reordered');
			return true;
		}
		else{
			/** @var FieldType $child */
			foreach ($fieldType->getChildren() as $child){
				if(!$child->getDeleted() && $this->reorderContentType($formArray[$child->getName()], $child)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Edit a content type; generic information, but also add subfields.
	 * Each times that a content type is saved the flag dirty is turned on.
	 *
	 * @param integer $id        	
	 * @param Request $request
	 *        	@Route("/content-type/{id}", name="contenttype.edit"))
	 */
	public function editAction($id, Request $request) {
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:ContentType' );
		
		/** @var ContentType $contentType */
		$contentType = $repository->find ( $id );
		
		if (! $contentType || count ( $contentType ) != 1) {
			$this-> addFlash ( 'warning', 'Content type not found.' );
			return $this->redirectToRoute ( 'contenttype.index' );
		}
		
		if (null == $contentType->getFieldType ()) {
			$fieldType = new FieldType ();
			$fieldType->setName ( 'source' );
			$fieldType->setType ( ContainerType::class );
			$fieldType->setContentType ( $contentType );
			$contentType->setFieldType ( $fieldType );
		}
		
		$inputContentType = $request->request->get ( 'content_type' );
		
		$form = $this->createForm ( ContentTypeType::class, $contentType );
		
		$form->handleRequest ( $request );
		
		if ($form->isSubmitted () && $form->isValid ()) {
			
			if (array_key_exists ( 'save', $inputContentType )) {
				$contentType->getFieldType ()->updateOrderKeys ();
				$contentType->setDirty ( $contentType->getEnvironment ()->getManaged () );
				$em->persist ( $contentType );
				$em->flush ();
				
				$this->addFlash ( 'warning', 'Content type has beend saved. Please consider to update the Elasticsearch mapping.' );
				return $this->redirectToRoute ( 'contenttype.index' );
			} else {
				if ($this->addNewContentType ( $inputContentType ['fieldType'], $contentType->getFieldType () )) {
					$contentType->getFieldType ()->updateOrderKeys ();
					$em->persist ( $contentType );
					$em->flush ();
					$this->addFlash ( 'notice', 'A new field has been added.' );
					return $this->redirectToRoute ( 'contenttype.edit', [ 
							'id' => $id 
					] );
				}
				
				if ($this->removeContentType ( $inputContentType ['fieldType'], $contentType->getFieldType () )) {
					$contentType->getFieldType ()->updateOrderKeys ();
					$em->persist ( $contentType );
					$em->flush ();
					$this->addFlash ( 'notice', 'A field has been removed.' );
					return $this->redirectToRoute ( 'contenttype.edit', [ 
							'id' => $id 
					] );
				}
				
				if ($this->reorderContentType ( $inputContentType ['fieldType'], $contentType->getFieldType () )) {
					// $contentType->getFieldType()->updateOrderKeys();
					$em->persist ( $contentType );
					$em->flush ();
					$this->addFlash ( 'notice', 'Fields have been reordered.' );
					return $this->redirectToRoute ( 'contenttype.edit', [ 
							'id' => $id 
					] );
				}
			}
		}
		
		/** @var  Client $client */
		$client = $this->get ( 'app.elasticsearch' );
		
		$mapping = $client->indices ()->getMapping ( [ 
				'index' => $contentType->getEnvironment ()->getAlias (),
				'type' => $contentType->getName () 
		] );
		
		return $this->render ( 'contenttype/edit.html.twig', [ 
				'form' => $form->createView (),
				'contentType' => $contentType,
				'mapping' => isset ( current ( $mapping ) ['mappings'] [$contentType->getName ()] ['properties'] ) ? current ( $mapping ) ['mappings'] [$contentType->getName ()] ['properties'] : false 
		] );
	}
}