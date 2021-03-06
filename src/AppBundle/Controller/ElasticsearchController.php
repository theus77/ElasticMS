<?php
namespace AppBundle\Controller;

use AppBundle\Controller\AppController;
use AppBundle\Entity\Form\Search;
use AppBundle\Entity\Form\SearchFilter;
use AppBundle\Entity\Template;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\RenderOptionType;
use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Form\Form\SearchFormType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\EnvironmentRepository;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use ZipStream\ZipStream;
use AppBundle\Service\ContentTypeService;
use AppBundle\Service\EnvironmentService;
use AppBundle\Entity\ContentType;
class ElasticsearchController extends AppController
{
	/**
	 * Create an alias for an index
	 *
	 * @param string indexName
	 * @param Request $request
	 * @Route("/elasticsearch/alias/add/{name}", name="elasticsearch.alias.add"))
	 */
	public function addAliasAction($name, Request $request) {
	
		/** @var  Client $client */
		$client = $this->get ( 'app.elasticsearch' );
		$result = $client->indices()->getAlias(['index' => $name]);
		
		$form = $this->createFormBuilder ( [] )->add ( 'name', IconTextType::class, [
				'icon' => 'fa fa-key',
				'required' => true
		] )->add ( 'save', SubmitEmsType::class, [
				'label' => 'Add',
				'icon' => 'fa fa-plus',
				'attr' => [
						'class' => 'btn btn-primary pull-right'
				]
		] )->getForm ();
		
		$form->handleRequest ( $request );
		
		if ( $form->isSubmitted () && $form->isValid ()) {
			$params ['body'] = [
				 'actions' => [
					 [
						 'add' => [
							 'index' => $name,
							 'alias' => $form->get('name')->getData(),
						 ]
					 ]
				 ]
			 ];
			
			 $client->indices ()->updateAliases ( $params );
			 $this->addFlash('notice', 'A new alias "'.$form->get('name')->getData().'" has been added to the index "'.$name.'"');

			 return $this->redirectToRoute("environment.index");
		}
		

		return $this->render( 'elasticsearch/add-alias.html.twig',[
			'form' => $form->createView(),
			'name' => $name,
		]);
	}
	
	/**
	 * @Route("/status.{_format}", defaults={"_format": "html"}, name="elasticsearch.status"))
	 */
	public function statusAction($_format)
	{
		try {
			$client = $this->get('app.elasticsearch');
			$status = $client->cluster()->health();
			
			if('html' === $_format && 'green' !== $status['status']){
				if('red' === $status['status']){
					$this->addFlash(
						'error',
						'There is something wrong with the cluster! Actions are required now.'
					);
				}
				else {
					$this->addFlash(
						'warning',
						'There is something wrong with the cluster. Status: <strong>'.$status['status'].'</strong>.'
					);
				}
			}
			
			return $this->render( 'elasticsearch/status.'.$_format.'.twig', [
					'status' => $status
			] );			
		}
		catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e){
			return $this->render( 'elasticsearch/no-nodes-available.'.$_format.'.twig', [
					'cluster' => $this->getParameter('elasticsearch_cluster'),
			]);
		}
	}
	

	/**
	 * @Route("/elasticsearch/delete-search/{id}", name="elasticsearch.search.delete"))
	 */
	public function deleteSearchAction($id, Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$repository = $em->getRepository('AppBundle:Form\Search');
		
		$search = $repository->find($id);
		if(!$search) {
			$this->createNotFoundException('Preset saved search not found');
		}
		
		$em->remove($search);
		$em->flush();
		
		return $this->redirectToRoute("elasticsearch.search");
	}

	/**
	 * @Route("/elasticsearch/index/delete/{name}", name="elasticsearch.index.delete"))
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
			$this->addFlash('notice', 'Elasticsearch index '.$name.' has been deleted');
		}
		catch (Missing404Exception $e){
			$this->addFlash('warning', 'Elasticsearch index not found');
		}
		return $this->redirectToRoute('environment.index');
	}
	
	/**
	 * @Route("/search.json", name="elasticsearch.api.search"))
	 */
	public function searchApiAction(Request $request)
	{
		$pattern = $request->query->get('q');
		$environments = $request->query->get('environment');
		$types = $request->query->get('type');
		$category = $request->query->get('category', false);
		// Added for ckeditor adv_link plugin.
		$assetName = $request->query->get('asset_name', false);
		
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
			
		/** @var ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository ( 'AppBundle:ContentType' );
		
		$allTypes = $contentTypeRepository->findAllAsAssociativeArray();
	
		if (empty($types)) {	
			$types = [];
			// For search only in contentType with Asset field == $assetName.
			if ($assetName) {
				foreach ($allTypes as $key => $value) {
					if (!empty($value->getAssetField())) {
						$types[] = $key;
					}
				}	
			} else {
				$types = array_keys($allTypes);
			}
		}
		else {
			$types = explode(',', $types);
		}
		
			
		if(!empty($types)){
			$aliases = [];
			$service = $this->get('ems.service.environment');
			if(empty($environments)){
				/**@var EnvironmentService $service*/
				foreach ($types as $type){
					
					$ct = $contentTypeRepository->findByName($type);
					if($ct) {
						$alias = $service->getAliasByName($ct->getEnvironment()->getName());
						if($alias){
							$aliases[] =  $alias->getAlias();
						}						
					}
				}			
			}
			else {
				$environments = explode(',', $environments);
				foreach ($environments as $environment) {
					$alias = $service->getAliasByName($environment);
					if($alias){
						$aliases[] =  $alias->getAlias();
					}
				}
			}
						
			$params = [
					'index' => array_unique($aliases),
					'type' => array_unique($types),
					'size' => $this->container->getParameter('paging_size'),
					'body' => [
						'query' => [
							'bool' => [
								'must' => []
							]
						]
					]
			
			];
			
			$matches = [];
			if(preg_match('/^[a-z][a-z0-9\-_]*:/i', $pattern, $matches)){
				$filterType =  substr($matches[0], 0, strlen($matches[0])-1);
				if(in_array($filterType, $types, true)){
					$pattern = substr($pattern, strlen($matches[0]));
					if($pattern === false){
						$pattern = '';
					}
					$params['type'] = $filterType;
				}
			}
			
			
			$patterns = explode(' ', $pattern);
			
			for($i=0; $i < (count($patterns)-1); ++$i){
				$params['body']['query']['bool']['must'][] = [
						'query_string' => [
						'default_field' => '_all',
						'query' => $patterns[$i],
					]
				];
			}
			
			$params['body']['query']['bool']['must'][] = [
					'query_string' => [
					'default_field' => '_all',
					'query' => '*'.$patterns[$i].'*',
				]
			];
			
			if(count($types) == 1){
				/**@var ContentTypeService $contentTypeService*/ 
				$contentTypeService = $this->get('ems.service.contenttype');
				$contentType = $contentTypeService->getByName($types[0]);
				if($contentType && $contentType->getOrderField()) {
					$params['body']['sort'] = [
						$contentType->getOrderField() => [
								'order' => 'asc',
								'missing' => '_last',
						]
					];
				}
// 				else if($contentType && $contentType->getLabelField()) {
// 					$params['body']['sort'] = [
// 						$contentType->getLabelField() => [
// 								'order' => '_id',
// 						]
// 					];
// 				}
				
				if($category && $contentType && $contentType->getCategoryField()) {
					$params['body']['query']['bool']['must'][] = [
							'term' => [
									$contentType->getCategoryField() => [
											'value' => $category
									]
							]
					];
				}			
			}
			
	
			/** @var \Elasticsearch\Client $client */
			$client = $this->get('app.elasticsearch');
			
			$results = $client->search($params);
		}
		//ther is no type matching this request
		else {
			$results = [
				'hits' => [
						'total' => 0,
						'hits' => [],
				],
			];
			
		}
		
		
		return $this->render( 'elasticsearch/search.json.twig', [
				'results' => $results,
				'types' => $allTypes,
		] );
		
	}
	
	
	private function getAllContentType($results) {
		$out = [];
		foreach ($results['aggregations']['types']['buckets'] as $type) {
			$out[] = $type['key'];
		}
		return $out;
	}
	
	/**
	 * @Route("/search/{query}", defaults={"query"=null}, name="elasticsearch.search"))
	 */
	public function searchAction($query, Request $request)
	{
		try {
			$search = new Search();
			
			//Save the form (uses POST method)
			if ($request->getMethod() == "POST"){
// 				$request->query->get('search_form')['name'] = $request->request->get('form')['name'];
				$request->request->set('search_form', $request->query->get('search_form'));
				
				
				$form = $this->createForm ( SearchFormType::class, $search);
				
				$form->handleRequest ( $request );
				/** @var Search $search */
				$search = $form->getData();
				$search->setName($request->request->get('form')['name']);
				$search->setUser($this->getUser()->getUsername());
				
				/** @var SearchFilter $filter */
				foreach ($search->getFilters() as $filter){
					$filter->setSearch($search);
				}
				
				$em = $this->getDoctrine()->getManager();
				$em->persist($search);
				$em->flush();
				
				return $this->redirectToRoute('elasticsearch.search', [
						'searchId' => $search->getId()
				]);	
			}
			
			if(null != $request->query->get('page')){
				$page = $request->query->get('page');
			}
			else{
				$page = 1;
			}
			
			//Use search from a saved form
			$searchId = $request->query->get('searchId');
			if(null != $searchId){
				$em = $this->getDoctrine()->getManager();
				$repository = $em->getRepository('AppBundle:Form\Search');
				$search = $repository->find($request->query->get('searchId'));
				if(! $search){
					$this->createNotFoundException('Preset search not found');
				}
			}
			
			
			$form = $this->createForm ( SearchFormType::class, $search, [
					'method' => 'GET',
					'savedSearch' => $searchId,
			] );
			

			$form->handleRequest ( $request );

			$openSearchForm = $form->get('search')->isClicked();
			
			//Form treatement after the "Save" button has been pressed (= ask for a name to save the search preset)
			if($form->isValid() && $request->query->get('search_form') && array_key_exists('save', $request->query->get('search_form'))) {
				
				$form = $this->createFormBuilder($search)
				->add('name', \Symfony\Component\Form\Extension\Core\Type\TextType::class)
				->add('save_search', SubmitEmsType::class, [
						'label' => 'Save',
						'attr' => [
								'class' => 'btn btn-primary pull-right'
						],
						'icon' => 'fa fa-save',
				])
				->getForm();
				
				return $this->render( 'elasticsearch/save-search.html.twig', [
						'form' => $form->createView(),
				] );				
			}//Form treatement after the "Delete" button has been pressed (to delete a previous saved search preset)
			else if($form->isValid() && $request->query->get('search_form') && array_key_exists('delete', $request->query->get('search_form'))) {
					$this->addFlash('notice', 'Search has been deleted');
			}
			
			//Next we want 1. see the results, or 2. export the results
			//So the common step is to fetch the results based on the search presets
			/** @var Search $search */
			if($request->query->get('form') && array_key_exists('massExport', $request->query->get('form'))){
				//In case of export we saved the search object in json form, time to recuperate it
				$jsonSearch = $request->query->get('form')['search-data'];
				$encoders = array(new JsonEncoder());
				$normalizers = array(new ObjectNormalizer());
				$serializer = new Serializer($normalizers, $encoders);
				
				$searchArray = json_decode($jsonSearch, true);
				$filtersArray = $searchArray['filters'];
				
				$searchArray['filters'] = null;
				
				$search =  $serializer->deserialize(json_encode($searchArray), Search::class, 'json');
				foreach ($filtersArray as $rawFilter){
					$jsonFilter = json_encode($rawFilter);
					$filter = $serializer->deserialize($jsonFilter, SearchFilter::class, 'json');
					$search->addFilter($filter);
				}
			}else{
				$search = $form->getData();
			}

			
			$body = $this->getSearchService()->generateSearchBody($search);
			
// 			
			
			/** @var EntityManager $em */
			$em = $this->getDoctrine()->getManager();
			
			/** @var ContentTypeRepository $contentTypeRepository */
			$contentTypeRepository = $em->getRepository ( 'AppBundle:ContentType' );
				
			$types = $contentTypeRepository->findAllAsAssociativeArray();
			
			/** @var EnvironmentRepository $environmentRepository */
			$environmentRepository = $em->getRepository ( 'AppBundle:Environment' );
			
			$environments = $environmentRepository->findAllAsAssociativeArray('alias');

			/** @var \Elasticsearch\Client $client */
			$client = $this->get('app.elasticsearch');
			
			$assocAliases = $client->indices()->getAliases();
			
			$mapAlias = [];
			$mapIndex = [];
			foreach ($assocAliases as $index => $aliasNames){
				foreach ($aliasNames['aliases'] as $alias => $options){
					if(isset($environments[$alias])){
						$mapAlias[$environments[$alias]['alias']] = $environments[$alias];
						$mapIndex[$index] = $environments[$alias];
						break;
					}
				}
			}

			$selectedEnvironments = [];
			if(!empty($search->getEnvironments() )){
				foreach($search->getEnvironments() as $envName){
					$temp = $this->getEnvironmentService()->getAliasByName($envName);
					if($temp){
						$selectedEnvironments[] = $temp->getAlias();
					}
				}				
			}
			
			
			//1. Define the parameters for a regular search request
			$params = [
					'version' => true, 
// 					'df'=> isset($field)?$field:'_all',
					'index' => empty($selectedEnvironments)?array_keys($environments):$selectedEnvironments,
					'type' => empty($search->getContentTypes())?array_keys($types):array_values($search->getContentTypes()),
					'size' => $this->container->getParameter('paging_size'), 
					'from' => ($page-1)*$this->container->getParameter('paging_size')
				
			];
			
			//2. Override parameters because when exporting we need all results, not paged
			if($request->query->get('form') && array_key_exists('massExport', $request->query->get('form'))){
				//TODO: size 10000 is the default maximum size of an elasticsearch installation. In case of export it would be better to use the scroll API of elasticsearch in case of performance issues. Or when more then 10000 results are going to be exported.
				//TODO: consideration: will there be an export limit? Because for giant loads of data it might be better to call an API of the system that needs our exported data. Then again, they could simply connect to elasticsearch as a standalone application!
				$params['from'] = 0;
				$params['size'] = 10000;
			}
		
			
			// 			   "highlight": {
			// 			      "fields": {
			// 			         "_all": {}
			// 			      }
			// 			   },			
			$body = array_merge($body, json_decode('{
			   "aggs": {
			      "types": {
			         "terms": {
			            "field": "_type",
						"size": 15
			         }
			      },
			      "indexes": {
			         "terms": {
			            "field": "_index",
						"size": 15
			         }
			      }
			   }
			}', true));
			

			
			$params['body'] = $body;
			
			try {
				$results = $client->search($params);
				$lastPage = ceil($results['hits']['total']/$this->container->getParameter('paging_size'));
			}
			catch (ElasticsearchException $e) {
				$this->addFlash('warning', $e->getMessage());
				$lastPage = 0;
				$results = ['hits' => ['total' => 0]];
			}
	

			$currentFilters = $request->query;
			$currentFilters->remove('search_form[_token]');
			
			//Form treatement after the "Export results" button has been pressed (= ask for a "content type" <-> "template" mapping)
			if($form->isValid() && $request->query->get('search_form') && array_key_exists('exportResults', $request->query->get('search_form'))) {
				//Store all the content types present in the current resultset
				$contentTypes = $this->getAllContentType($results);

				/**@var ContentTypeService $contenttypeService*/
				$contenttypeService = $this->get('ems.service.contenttype');
				/**@var EnvironmentService $environmentService*/
				$environmentService = $this->get('ems.service.environment');
				
				//Check for each content type that an export template is available. 
				//If no export template is defined, ignore the content type.
				//If one or more export templates are defined, allow choice of the template to be dynamic
				$form = null;
				foreach ($contentTypes as $name){
					/** @var ContentType $contentType */
					$contentType = $types[$name];
				
					$templateChoices = ['JSON export' => 0];
					/** @var Template $template */
					foreach ($contentType->getTemplates() as $template){
						if (RenderOptionType::EXPORT == $template->getRenderOption() && $template->getBody()){
							$templateChoices[$template->getName()] = $template->getId();
						}
					}
				
					if (!empty($templateChoices)){
						if (!$form){
							$encoders = array(new JsonEncoder());
							$normalizers = array(new ObjectNormalizer());
							$serializer = new Serializer($normalizers, $encoders);
							$jsonSearch = $serializer->serialize($search, 'json');
							
					 		$form = $this->createFormBuilder()
					 			->setMethod('GET')
					 			->add('search-data', HiddenType::class, array(
					 					'data' => $jsonSearch,
					 			));
					 	}
					 	$form->add($name, ChoiceType::class, array (
					 			'label' => 'Export template for '.$contenttypeService->getByName($name)->getPluralName(),
					 			'choices' => $templateChoices,
					 	));
					}
				}
				
				if ($form) {
					$form = $form->add('massExport', SubmitType::class)->getForm();
					$form->handlerequest($request);
					return $this->render( 'elasticsearch/export-search.html.twig', [
							'form' => $form->createView(),
					] );
				}else{
					return $this->render( 'elasticsearch/export-search.html.twig');
				}
				
			}
			
			//Form treatement after the "Mass export" button has been pressed (= download all the results with the given preset)
			if($request->query->get('form') && array_key_exists('massExport', $request->query->get('form'))){
				//TODO: ? CANNOT DO THE ISVALID CHECK HERE :(

				//Load the selected templates for each content type
				/** @var EntityManager $em */
				$em = $this->getDoctrine()->getManager();
				
				/** @var ContentTypeRepository $repository */
				$templateRepository = $em->getRepository('AppBundle:Template');
				
				$templateChoises = $request->query->get('form');
				
				$templateMapping = [];
				$templateBodyMapping = [];
				
				$twig = $this->getTwig();
				$errorList = [];
				foreach ($templateChoises as $contentName => $templateChoise){
					if ( 'search-data' != $contentName && 'massExport' != $contentName && '_token' != $contentName){
						$template = $templateRepository->find($templateChoise);
						
						if ($template) {
							$templateMapping[$contentName] = $template;
							
							try {
								//TODO why is the body generated and passed to the twig file while the twig file does not use it?
								//Asked by dame
								//If there is an error in the twig the user will get an 500 error page, this solution is not perfect but at least the template is tested
								$body = $twig->createTemplate($template->getBody());
							}
							catch (\Twig_Error $e){
								$this->addFlash('error', 'There is something wrong with the template '.$template->getName());
								$body = $twig->createTemplate('error in the template!');
								$errorList[] = "Error in template->getBody() for: ".$template->getName();
								continue;
							}
							
							$templateBodyMapping[$contentName] = $body;
						}
						else{
							//Default JSON export
							$templateMapping[$contentName] = NULL;
							$templateBodyMapping[$contentName] = NULL;
						}
					}
				}
				
				//Create the file for each result and accumulate in a zip stream
				$extime = ini_get('max_execution_time');
				ini_set('max_execution_time', 0);
				
				$fileTime = date("D, d M Y H:i:s T");
				$zip = new ZipStream("eMSExport.zip");
				
				$contentTypes = $this->getAllContentType($results);

				$resultsSize = count($results['hits']['hits']);
				$loop = [];
				$accumulatedContent = "";
				foreach ($results['hits']['hits'] as $result){
					if (array_key_exists('first', $loop)){
						$loop['first'] = false;
					} else {
						$loop['first'] = true;
					}
					if (array_key_exists('index0', $loop)) {
						$loop['index0'] = $loop['index0']+1;
					} else {
						$loop['index0'] = 0;
					}
					if (array_key_exists('index1', $loop)) {
						$loop['index1'] = $loop['index1']+1;
					} else {
						$loop['index1'] = 1;
					}
					$loop['last'] =  $resultsSize == $loop['index1'];
					
					$name = $result['_type'];
					
					$template = $templateMapping[$name];
					$body = $templateBodyMapping[$name];
					
					
					if($template) {
						$filename = $result['_id'];
						if (null != $template->getFilename()){
							try {
								$filename = $twig->createTemplate($template->getFilename());
							} catch (\Twig_Error $e) {
								$this->addFlash('error', 'There is something wrong with the template filename field '.$template->getName());
								$filename = $result['_id'];
								$errorList[] = "Error in template->getFilename() for: ".$filename;
								continue;
							}
							$filename = $filename->render([
									'loop' => $loop,
									'contentType' => $template->getContentType(),
									'object' => $result,
									'source' => $result['_source'],
							]);
							$filename = preg_replace('~[\r\n]+~', '', $filename);
						}
						if(null!= $template->getExtension()){
							$filename = $filename.'.'.$template->getExtension();
						}
						
						try {
							$content = $body->render([
										'loop' => $loop,
										'contentType' => $template->getContentType(),
										'object' => $result,
										'source' => $result['_source'],
								]);
						}catch (\Twig_Error $e)
						{
							$this->addFlash('error', 'There is something wrong with the template filename field '.$template->getName());
							$content = "There was an error rendering the content";
							$errorList[] = "Error in templateBody->render() for: ".$filename;
							continue;
						}
						
						if ($template->getAccumulateInOneFile()){
							$accumulatedContent = $accumulatedContent.$content;
							if ($loop['last']){
								$zip->addFile($template->getName().'.'.$template->getExtension(), $accumulatedContent);
							}
						} else {
							$zip->addFile($filename, $content);
						}
					}
					else {
						//JSON export

						$zip->addFile($result['_type'].' '.$result['_id'].'.json', json_encode($result['_source']));
					}
					
				}
				
				if (!empty($errorList))
				{
					$zip->addFile("All-Errors.txt", implode("\n", $errorList));
				}
				
				$zip->finish();
				exit;
			}
			
			return $this->render( 'elasticsearch/search.html.twig', [
					'results' => $results,
					'lastPage' => $lastPage,
					'paginationPath' => 'elasticsearch.search',
					'types' => $types,
					'alias' => $mapAlias,
					'indexes' => $mapIndex,
					'form' => $form->createView(),
					'page' => $page,
					'searchId' => $searchId,
					'currentFilters' => $request->query,
					'body' => $body,
					'openSearchForm' => $openSearchForm,
					'search' => $search,
			] );
		}
		catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e){
			return $this->redirectToRoute('elasticsearch.status');
		}
	}
	
	
}