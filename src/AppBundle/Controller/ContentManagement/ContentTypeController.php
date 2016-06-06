<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle;
use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle\Entity\Environment;
use AppBundle\Entity\FieldType;
use AppBundle\Entity\Helper\JsonNormalizer;
use AppBundle\Form\DataField\SubfieldType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Form\Form\ContentTypeType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\EnvironmentRepository;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Form\Form;

/**
 * Operations on content types such as CRUD but alose rebuild index.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class ContentTypeController extends AppController {
	
	
	public static function isValidName($name) {
		return preg_match('/^[a-z][a-z0-9\-_]*$/i', $name) && strlen($name) <= 100;
	}
	
	
	
	/**
	 * Logically delete a content type.
	 * GET calls aren't supported.
	 *
	 * @param integer $id
	 *        	identifier of the content type to delete
	 * @param Request $request
	 * 
	 * @Route("/content-type/remove/{id}", name="contenttype.remove"))
     * @Method({"POST"})
	 *        
	 */
	public function removeAction($id, Request $request) {
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:ContentType' );
		
		/** @var ContentType $contentType */
		$contentType = $repository->find ( $id );
		
		if (!$contentType || count ( $contentType ) != 1) {
			throw new NotFoundHttpException('Content Type not found');
		}
		
		//TODO test if there something published for this content type 
		$contentType->setActive ( false )->setDeleted ( true );
		$em->persist ( $contentType );
		$em->flush ();
		$this->addFlash ( 'warning', 'Content type ' . $contentType->getName () . ' has been deleted' );
		
		return $this->redirectToRoute ( 'contenttype.index' );
	}
	
	/**
	 * Activate (make it available for authors) a content type.
	 * Checks that the content isn't dirty (as far as eMS knows the Mapping in Elasticsearch is up-to-date).
	 *
	 * @param integer $id        	
	 * @param Request $request
	 * 
	 * @Route("/content-type/activate/{id}", name="contenttype.activate"))
     * @Method({"POST"})
	 */
	public function activateAction($id, Request $request) {
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:ContentType' );
		
		/** @var ContentType $contentType */
		$contentType = $repository->find ( $id );
		
		if (! $contentType || count ( $contentType ) != 1) {
			$this->addFlash ( 'warning', 'Content type not found!' );
			return $this->redirectToRoute ( 'contenttype.index' );
		}
		
		if ($contentType->getDirty ()) {
			$this->addFlash ( 'warning', 'Content type "' . $contentType->getName () . '" is dirty (its mapping migth be out-of date).
					Try to update its mapping.' );
			return $this->redirectToRoute ( 'contenttype.index' );
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
	 * 
     * @Method({"POST"})
	 */
	public function refreshMappingAction($id, Request $request) {
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:ContentType' );
		
		/** @var ContentType $contentType */
		$contentType = $repository->find ( $id );
		
		if (! $contentType || count ( $contentType ) != 1) {
			$this->addFlash ( 'warning', 'Content type not found!' );
			return $this->redirectToRoute ( 'contenttype.index' );
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
					'body' => $this->get('ems.service.mapping')->generateMapping ($contentType)
			] );
			
			if (isset ( $out ['acknowledged'] ) && $out ['acknowledged']) {
				$contentType->setDirty ( false );
				$this->addFlash ( 'notice', 'Mappings successfully updated' );
			} else {
				$contentType->setDirty ( true );
				$this->addFlash ( 'warning', '<p><strong>Something went wrong. Try again</strong></p>
						<p>Message from Elasticsearch: ' . print_r ( $out, true ) . '</p>' );
			}
			
		} catch ( BadRequest400Exception $e ) {
			$contentType->setDirty ( true );
			$message = json_decode($e->getPrevious()->getMessage(), true);
			$this->addFlash ( 'error', '<p><strong>You should try to rebuild the indexes</strong></p>
					<p>Message from Elasticsearch: <b>' . $message['error']['type']. '</b>'.$message['error']['reason'] . '</p>' );
		}
		$em->persist ( $contentType );
		$em->flush ();		
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
		
		$contentTypeAdded = new ContentType ();
		$form = $this->createFormBuilder ( $contentTypeAdded )->add ( 'name', IconTextType::class, [ 
				'icon' => 'fa fa-gear',
				'label' => "Machine name",
				'required' => true
		] )->add ( 'pluralName', TextType::class, [ 
				'label' => 'Plural form' 
		] )->add ( 'import', FileType::class, [ 
				'label' => 'Import From JSON',
				'mapped' => false,
				'required' => false
		] )->add ( 'environment', ChoiceType::class, [
				'label' => 'Default environment',
				'choices' => $environments,
				/** @var Environment $environment */
				'choice_label' => function ($environment, $key, $index) {
					return $environment->getName ();
				}
		] )->add ( 'save', SubmitType::class, [ 
				'label' => 'Create',
				'attr' => [ 
						'class' => 'btn btn-primary pull-right' 
				] 
		] )->getForm ();
		
		$form->handleRequest ( $request );
		
		if ($form->isSubmitted () && $form->isValid ()) {
			/** @var ContentType $contentType */
			$contentTypeAdded = $form->getData ();
			$contentTypeRepository = $em->getRepository ( 'AppBundle:ContentType' );
			
			$contentTypes = $contentTypeRepository->findBy ( [ 
					'name' => $contentTypeAdded->getName () ,
					'deleted' => false
			] );
			
			if (count ( $contentTypes ) != 0) {
				$form->get ( 'name' )->addError ( new FormError ( 'Another content type named ' . $contentTypeAdded->getName () . ' already exists' ) );
			}
			
			if(!$this->isValidName($contentTypeAdded->getName () )){
				$form->get ( 'name' )->addError ( new FormError ( 'The content type name is malformed (format: [a-z][a-z0-9_-]*)' ) );
			}
			
			if ($form->isValid ()) {
				$normData = $form->get("import")->getNormData();
				if($normData){
					$name = $contentTypeAdded->getName();
					$pluralName = $contentTypeAdded->getPluralName();
					$environment = $contentTypeAdded->getEnvironment();
					/** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
					$file = $request->files->get('form')['import'];
					$fileContent = file_get_contents($file->getRealPath());
					
					$encoders = array(new JsonEncoder());
					$normalizers = array(new JsonNormalizer());
					$serializer = new Serializer($normalizers, $encoders);
					$contentType = $serializer->deserialize($fileContent, 
															"AppBundle\Entity\ContentType", 
															'json');
					$contentType->setName($name);
					$contentType->setPluralName($pluralName);
					$contentType->setEnvironment($environment);
					$contentType->setActive(false);
					$contentType->getFieldType()->updateAncestorReferences($contentType, NULL);
					$contentType->setOrderKey($contentTypeRepository->countContentType());

					$em->persist ( $contentType );
				}
				else {
					$contentType = $contentTypeAdded;
					$contentType->setOrderKey($contentTypeRepository->countContentType());
					$em->persist ( $contentType );
				}
				$em->flush ();
				$this->addFlash ( 'notice', 'A new content type ' . $contentTypeAdded->getName () . ' has been created' );
				
				return $this->redirectToRoute ( 'contenttype.edit', [
						'id' => $contentType->getId ()
				] );
				
			} else {
				$this->addFlash ( 'error', 'Invalid form.' );
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

		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		
		/** @var ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository ( 'AppBundle:ContentType' );
		
		$contentTypes = $contentTypeRepository->findBy(['deleted' => false], ['orderKey'=>'ASC']);
		
		$builder = $this->createFormBuilder ( [] )
			->add ( 'reorder', SubmitEmsType::class, [
    				'attr' => [
    						'class' => 'btn-primary '
    				],
    				'icon' => 'fa fa-reorder'    			
    		] );
		
		$names = [];	
		foreach ($contentTypes as $contentType) {
			$names[] = $contentType->getName();
		}
		
		$builder->add('contentTypeNames', CollectionType::class, array(
				// each entry in the array will be an "email" field
				'entry_type'   => HiddenType::class,
				// these options are passed to each "email" type
				'entry_options'  => array(
				),
				'data' => $names
		));
    		
    	$form = $builder->getForm ();
    	
    	if ($request->isMethod('POST')) {
			$form = $request->get('form');
			if(isset($form['contentTypeNames']) && is_array($form['contentTypeNames'])){
				$counter = 0;
				foreach ($form['contentTypeNames'] as $name){
					
					$contentType = $contentTypeRepository->findOneBy([
							'deleted' => false,
							'name' => $name
					]);
					if($contentType){
						$contentType->setOrderKey($counter);
						$em->persist($contentType);
					}
					++$counter;
				}
				
				$em->flush();
	    		$this->addFlash('notice', 'Content types have been reordered');
			}
    	
    		return $this->redirectToRoute('contenttype.index');
    	}
		
		return $this->render ( 'contenttype/index.html.twig', [
				'form' => $form->createView (),
		] );
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
		$contentTypeRepository = $em->getRepository ( 'AppBundle:ContentType' );
		
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
					$contentType->setOrderKey($contentTypeRepository->countContentType());
					
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
		
		return $this->render ( 'contenttype/unreferenced.html.twig', [ 
				'referencedContentTypes' => $referencedContentTypes 
		] );
	}
	
	/**
	 * Try to find (recursively) if there is a new field to add to the content type
	 * 
	 * @param array $formArray        	
	 * @param FieldType $fieldType        	
	 */
	private function addNewField(array $formArray, FieldType $fieldType) {
		if (array_key_exists ( 'add', $formArray )) {
			if(isset($formArray ['ems:internal:add:field:name']) 
					&& strcmp($formArray ['ems:internal:add:field:name'], '') != 0
					&& isset($formArray ['ems:internal:add:field:class']) 
					&& strcmp($formArray ['ems:internal:add:field:class'], '') != 0) {
				if($this->isValidName($formArray ['ems:internal:add:field:name'])){
					$child = new FieldType ();
					$child->setName ( $formArray ['ems:internal:add:field:name'] );
					$child->setType ( $formArray ['ems:internal:add:field:class'] );
					$child->setParent ( $fieldType );
					$fieldType->addChild ( $child );
					$this->addFlash('notice', 'The field '.$child->getName().' has been prepared to be added');					
				}
				else {
					$this->addFlash('error', 'The field\'s name is not valid (format: [a-z][a-z0-9_-]*)');
				}
			}
			else {
				$this->addFlash('error', 'The field\'s name and type are mandatory');
			}
			return true;
		} else {
			/** @var FieldType $child */
			foreach ( $fieldType->getChildren () as $child ) {
				if (! $child->getDeleted () && $this->addNewField ( $formArray ['ems_'.$child->getName ()], $child )) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Try to find (recursively) if there is a new field to add to the content type
	 * 
	 * @param array $formArray        	
	 * @param FieldType $fieldType        	
	 */
	private function addNewSubfield(array $formArray, FieldType $fieldType) {
		if (array_key_exists ( 'subfield', $formArray )) {
			if(isset($formArray ['ems:internal:add:subfield:name']) 
					&& strcmp($formArray ['ems:internal:add:subfield:name'], '') !== 0) {
				if($this->isValidName($formArray ['ems:internal:add:subfield:name'])) {
					$child = new FieldType ();
					$child->setName ( $formArray ['ems:internal:add:subfield:name'] );
					$child->setType ( SubfieldType::class );
					$child->setParent ( $fieldType );
					$fieldType->addChild ( $child );
					$this->addFlash('notice', 'The subfield '.$fieldType->getName().' has been prepared to be added');
				}
				else {
					$this->addFlash('error', 'The subfield\'s name is not valid (format: [a-z][a-z0-9_-]*)');
				}
			}
			else{
				$this->addFlash('notice', 'The subfield name is mandatory');
			}
			return true;
		} else {
			/** @var FieldType $child */
			foreach ( $fieldType->getChildren () as $child ) {
				if (! $child->getDeleted () && $this->addNewSubfield ( $formArray ['ems_'.$child->getName ()], $child )) {
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
	private function removeField(array $formArray, FieldType $fieldType){
		if(array_key_exists('remove', $formArray)){
			$fieldType->setDeleted(true);
			$this->addFlash('notice', 'The field '.$fieldType->getName().' has been prepared to be removed');
			return true;
		}
		else{
			/** @var FieldType $child */
			foreach ($fieldType->getChildren() as $child){
				if(!$child->getDeleted() && $this->removeField($formArray['ems_'.$child->getName()], $child)) {
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
	private function reorderFields(array $formArray, FieldType $fieldType){
		if(array_key_exists('reorder', $formArray)){
			$keys = array_keys($formArray);
			/** @var FieldType $child */
			dump($keys);
			foreach ($fieldType->getChildren() as $child){
				if(! $child->getDeleted() ){
					$child->setOrderKey(array_search('ems_'.$child->getName(), $keys));
				}
			}

			$this->addFlash('notice', 'Subfields in '.$fieldType->getName().' has been prepared to be reordered');
			return true;
		}
		else{
			/** @var FieldType $child */
			foreach ($fieldType->getChildren() as $child){
				if(!$child->getDeleted() && $this->reorderFields($formArray['ems_'.$child->getName()], $child)) {
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
		
		$inputContentType = $request->request->get ( 'content_type' );
		
		$form = $this->createForm ( ContentTypeType::class, $contentType, [
			'twigWithWysiwyg' => $contentType->getEditTwigWithWysiwyg()
		] );
		
		$form->handleRequest ( $request );
		
		if ($form->isSubmitted () && $form->isValid ()) {
			$contentType->getFieldType()->setName('source');
			
			if (array_key_exists ( 'save', $inputContentType ) || array_key_exists ( 'saveAndClose', $inputContentType )) {
				$contentType->getFieldType ()->updateOrderKeys ();
				$contentType->setDirty ( $contentType->getEnvironment ()->getManaged () );


// 				exit;
				$em->persist ( $contentType );
				$em->flush ();
				if($contentType->getDirty()){
					$this->addFlash ( 'warning', 'Content type has beend saved. Please consider to update the Elasticsearch mapping.' );					
				}
				if (array_key_exists ( 'saveAndClose', $inputContentType )){
					return $this->redirectToRoute ( 'contenttype.index' );					
				}
				return $this->redirectToRoute ( 'contenttype.edit', [
						'id' => $id
				] );
			} else {
				if ($this->addNewField ( $inputContentType ['fieldType'], $contentType->getFieldType () )) {
					$contentType->getFieldType ()->updateOrderKeys ();
					
					$em->persist ( $contentType );
					$em->flush ();
					return $this->redirectToRoute ( 'contenttype.edit', [ 
							'id' => $id 
					] );
				}
				
				else if ($this->addNewSubfield( $inputContentType ['fieldType'], $contentType->getFieldType () )) {
					$contentType->getFieldType ()->updateOrderKeys ();
					$em->persist ( $contentType );
					$em->flush ();
					return $this->redirectToRoute ( 'contenttype.edit', [ 
							'id' => $id 
					] );
				}
				
				else if ($this->removeField ( $inputContentType ['fieldType'], $contentType->getFieldType () )) {
					$contentType->getFieldType ()->updateOrderKeys ();
					$em->persist ( $contentType );
					$em->flush ();
					$this->addFlash ( 'notice', 'A field has been removed.' );
					return $this->redirectToRoute ( 'contenttype.edit', [ 
							'id' => $id 
					] );
				}
				
				else if ($this->reorderFields ( $inputContentType ['fieldType'], $contentType->getFieldType () )) {
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
		
		if($contentType->getDirty()){
			$this->addFlash('warning', $contentType->getName().' is dirty. Consider to update its mapping.');
		}
		
		return $this->render ( 'contenttype/edit.html.twig', [ 
				'form' => $form->createView (),
				'contentType' => $contentType,
				'mapping' => isset ( current ( $mapping ) ['mappings'] [$contentType->getName ()] ['properties'] ) ? current ( $mapping ) ['mappings'] [$contentType->getName ()] ['properties'] : false 
		] );
	}
	
	

	/**
	 * Migrate a content type from its default index
	 *
	 * @param integer $id        	
	 * @param Request $request
     * @Method({"POST"})
	 * @Route("/content-type/migrate/{contentType}", name="contenttype.migrate"))
	 */	
	 public function migrateAction(ContentType $contentType, Request $request) {
	 	return $this->startJob('ems.contenttype.migrate', [
	 			'contentTypeName'    => $contentType->getName()
	 	]);
	 }
	
	
	/**
	 * Export a content type in Json format
	 *
	 * @param integer $id        	
	 * @param Request $request
	 *        	@Route("/content-type/export/{contentType}.{_format}", defaults={"_format" = "json"}, name="contenttype.export"))
	 */
	public function exportAction(ContentType $contentType, Request $request) {
		//Sanitize the CT
		$contentType->setCreated(NULL);
		$contentType->setModified(NULL);
		$contentType->getFieldType()->removeCircularReference();		
		$contentType->setEnvironment(NULL);
		$contentType->getTemplates()->clear();
		$contentType->getViews()->clear();
		
		//Serialize the CT
		$encoders = array(new JsonEncoder());
		$normalizers = array(new JsonNormalizer());
		$serializer = new Serializer($normalizers, $encoders);
		$jsonContent = $serializer->serialize($contentType, 'json');
		$response = new Response($jsonContent);
		$diposition = $response->headers->makeDisposition(
		    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
		    $contentType->getName().'.json'
		);
		
		$response->headers->set('Content-Disposition', $diposition);
		return $response;
	}
}