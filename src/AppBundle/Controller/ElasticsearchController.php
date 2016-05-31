<?php
namespace AppBundle\Controller;

use AppBundle\Controller\AppController;
use AppBundle\Entity\Form\Search;
use AppBundle\Entity\Form\SearchFilter;
use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Form\Form\SearchFormType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\EnvironmentRepository;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Template;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Form\Field\RenderOptionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use ZipStream\ZipStream;
use AppBundle\Entity\ExportMapping;
use AppBundle\Twig\AppExtension;
class ElasticsearchController extends AppController
{
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
		$environment = $request->query->get('environment');
		$type = $request->query->get('type');
		
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
			
		/** @var ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository ( 'AppBundle:ContentType' );
		
		$types = $contentTypeRepository->findAllAsAssociativeArray();
			
		/** @var EnvironmentRepository $environmentRepository */
		$environmentRepository = $em->getRepository ( 'AppBundle:Environment' );
			
		$environments = $environmentRepository->findAllAsAssociativeArray('alias');
		
		if(!$type){
			$type = array_keys($types);
		}
		
		
		$alias = array_keys($environments);
		if($environment){
			foreach ($environments as $item){
				if(strcmp($environment, $item['name']) == 0){
					$alias = $item['alias'];
				}
			}			
		}
		
		
		
		$params = [
				'index' => $alias,
				'type' => $type,
				'size' => $this->container->getParameter('paging_size'),
				'body' => [
						'query' => [
								'and' => [
										
								]
						]
				]
		
		];
		
		
		$patterns = explode(' ', $pattern);
		
		for($i=0; $i < (count($patterns)-1); ++$i){
			$params['body']['query']['and'][] = [
					'match' => [
							'_all' => $patterns[$i]
					]
			];
		}
		
		$params['body']['query']['and'][] = [
				'wildcard' => [
						'_all' => $patterns[$i].'*'
				]
		];
		

		/** @var \Elasticsearch\Client $client */
		$client = $this->get('app.elasticsearch');
		
		$results = $client->search($params);
		
		return $this->render( 'elasticsearch/search.json.twig', [
				'results' => $results,
				'types' => $types,
		] );
		
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

			$body = [];
			/** @var SearchFilter $filter */
			foreach ($search->getFilters() as $filter){
					
				$esFilter = $filter->generateEsFilter();
					
				if($esFilter){
					$body["query"][$search->getBoolean()][] = $esFilter;
				}	
					
			}			
			
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
			
// 			dump($mapAlias);
			//1. Define the parameters for a regular search request
			$params = [
					'version' => true, 
// 					'df'=> isset($field)?$field:'_all',
					'index' => $search->getAliasFacet() != null?$search->getAliasFacet():array_keys($environments),
					'type' => $search->getTypeFacet() != null?$search->getTypeFacet():array_keys($types),
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
		
			
			
			$body = array_merge($body, json_decode('{
			   "highlight": {
			      "fields": {
			         "_all": {}
			      }
			   },
			   "aggs": {
			      "types": {
			         "terms": {
			            "field": "_type"
			         }
			      },
			      "indexes": {
			         "terms": {
			            "field": "_index"
			         }
			      }
			   }
			}', true));
			

			
			$params['body'] = $body;
			
// 			dump($params);
			$results = $client->search($params);
	
		
			$lastPage = ceil($results['hits']['total']/$this->container->getParameter('paging_size'));
			
// 			dump($request);

// 			if($lastPage)

			$currentFilters = $request->query;
// 			$currentFilters->set('page', 1);
			$currentFilters->remove('search_form[_token]');
			
			//Form treatement after the "Export results" button has been pressed (= ask for a "content type" <-> "template" mapping)
			if($form->isValid() && $request->query->get('search_form') && array_key_exists('exportResults', $request->query->get('search_form'))) {
				//Store all the content types present in the current resultset
				$exportMapping = new ExportMapping();
				$exportMapping->addTemplates($results);
				
				//Check for each content type that an export template is available. 
				//If no export template is defined, ignore the content type.
				//If one or more export templates are defined, allow choice of the template to be dynamic
				$form = null;
				foreach ($exportMapping->getContentTypeNames() as $name){
					/** @var ContentType $contentType */
					$contentType = $types[$name];
				
					$templateChoices = [];
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
					 	$combinedName = $exportMapping->getCombinedName($name); 
					 	$form->add($combinedName, ChoiceType::class, array (
					 			'label' => 'Export template for '.$combinedName.' type: ',
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
							}
							
							$templateBodyMapping[$contentName] = $body;
						}
					}
				}
				
				//Create the xml of each result and accumulate in a zip stream
				$extime = ini_get('max_execution_time');
				ini_set('max_execution_time', 600);
				
				$fileTime = date("D, d M Y H:i:s T");
				$zip = new ZipStream("eMSExport.zip");
				
				$exportMapping = new ExportMapping();
				$exportMapping->addTemplates($results);
				
				foreach ($results['hits']['hits'] as $result){
					$name = $result['_type'];
					$formFieldName = $exportMapping->getCombinedName($name);
					$template = $templateMapping[$formFieldName];
					$body = $templateBodyMapping[$formFieldName];
					
					$filename = $result['_id'];
					if (null != $template->getFilename()){
						try {
							$filename = $twig->createTemplate($template->getFilename());
						} catch (\Twig_Error $e) {
							$this->addFlash('error', 'There is something wrong with the template filename field '.$template->getName());
							$filename = $twig->createTemplate('error in the template!');
						}
						
						$filename = $filename->render([
								'contentType' => $template->getContentType(),
								'object' => $result,
								'source' => $result['_source'],
						]);
						$filename = preg_replace('~[\r\n]+~', '', $filename);
					}
			
					if(null!= $template->getExtension()){
						$filename = $filename.'.'.$template->getExtension();
					}
					
					$zip->addFile(
							$filename,
							$body->render([
									'contentType' => $template->getContentType(),
									'object' => $result,
									'source' => $result['_source'],
							])
					);
				}
				
				$zip->finish();
				ini_set('max_execution_time', $extime);
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
			] );
		}
		catch (\Elasticsearch\Common\Exceptions\NoNodesAvailableException $e){
			return $this->redirectToRoute('elasticsearch.status');
		}
	}
	
	
}