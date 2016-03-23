<?php
namespace AppBundle\Controller;

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
use Symfony\Component\Serializer\Encoder\JsonEncode;

class ElasticsearchController extends Controller
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
			return $this->render( 'elasticsearch/no-nodes-available.'.$_format.'.twig');
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
		$environment = $request->request->get('environment');
		$type = $request->request->get('type');
		
		
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
			}
			else if($form->isValid() && $request->query->get('search_form') && array_key_exists('delete', $request->query->get('search_form'))) {
					$this->addFlash('notice', 'Search has been deleted');
			}
			
			/** @var Search $search */
			$search = $form->getData();
			
			
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
			$params = [
					'version' => true, 
// 					'df'=> isset($field)?$field:'_all',
					'index' => $search->getAliasFacet() != null?$search->getAliasFacet():array_keys($environments),
					'type' => $search->getTypeFacet() != null?$search->getTypeFacet():array_keys($types),
					'size' => $this->container->getParameter('paging_size'), 
					'from' => ($page-1)*$this->container->getParameter('paging_size')
				
			];
		
			
			
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