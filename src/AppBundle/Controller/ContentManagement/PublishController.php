<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle;
use AppBundle\Entity\ContentType;
use AppBundle\Entity\Environment;
use AppBundle\Entity\Revision;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\Field\EnvironmentPickerType;
use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Entity\Form\Search;
use AppBundle\Form\Form\SearchFormType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublishController extends AppController
{
	/**
	 * @Route("/publish/to/{revisionId}/{envId}", name="revision.publish_to"))
	 */
	public function publishToAction(Revision $revisionId, Environment $envId, Request $request)
	{
		$this->get("ems.service.publish")->publish($revisionId, $envId);
		
		return $this->redirectToRoute('data.revisions', [
				'ouuid' => $revisionId->getOuuid(),
				'type'=> $revisionId->getContentType()->getName(),
				'revisionId' => $revisionId->getId(),
		]);
	}
	
	/**
	 * @Route("/revision/unpublish/{revisionId}/{envId}", name="revision.unpublish"))
	 */
	public function unpublishAction(Revision $revisionId, Environment $envId, Request $request)
	{
		$this->get("ems.service.publish")->unpublish($revisionId, $envId);
		
		return $this->redirectToRoute('data.revisions', [
				'ouuid' => $revisionId->getOuuid(),
				'type'=> $revisionId->getContentType()->getName(),
				'revisionId' => $revisionId->getId(),
		]);		
	}
	
	/**
	 * @Route("/publish/search-result", name="search.publish", defaults={"deleted": 0, "managed": 1})
     * @Security("has_role('ROLE_PUBLISHER')")
	 */
	public function publishSearchResult( Request $request)
	{
		$search = new Search();
		$searchForm = $this->createForm ( SearchFormType::class, $search, [
				'method' => 'GET',
		] );
		$requestBis = clone $request;
		
		$requestBis->setMethod('GET');
		$searchForm->handleRequest ( $requestBis );
		
		/**@var Environment $environment */
		/**@var ContentType $contentType */
		if(count($search->getEnvironments()) != 1 && $this->getEnvironmentService()->getAliasByName($search->getEnvironments()[0])){
			throw new NotFoundHttpException('Environment not found');
		}
		if(count($search->getContentTypes()) != 1 && $contentType = $this->getContentTypeService()->getByName($search->getContentTypes()[0]))  {
			throw new NotFoundHttpException('Content type not found');
		}
		
		$environment = $this->getEnvironmentService()->getAliasByName($search->getEnvironments()[0]);
		$contentType = $this->getContentTypeService()->getByName($search->getContentTypes()[0]);
		
		$data = [];
		$builder = $this->createFormBuilder($data);
		$builder->add('toEnvironment', EnvironmentPickerType::class, [
			'managedOnly' => true,
			'ignore' => [$environment->getName()],
		])->add('publish', SubmitEmsType::class, [
				'attr' => [ 
						'class' => 'btn-primary btn-md' 
				],
				'icon' => 'glyphicon glyphicon-open'
		]);
		
		$body = $this->getSearchService()->generateSearchBody($search);
		$form = $builder->getForm();
		$form->handleRequest($request);
		
		$counter = $this->getElasticsearch()->search([
				'type' => $contentType->getName(),
				'index' => $environment->getAlias(),
				'body' => $body,
				'size' => 0,
		]);
		
		$total = $counter['hits']['total'];
		
		
		if($form->isSubmitted()) {
			$toEnvironment = $this->getEnvironmentService()->getAliasByName($form->get('toEnvironment')->getData());
			for($from = 0; $from < $total; $from = $from + 5) {
				$scroll = $this->getElasticsearch()->search([
					'type' => $contentType->getName(),
					'index' => $environment->getAlias(),
						'size' => 5,
						'from' => $from,
						'preference' => '_primary', //http://stackoverflow.com/questions/10836142/elasticsearch-duplicate-results-with-paging
				]);
				
				foreach ($scroll['hits']['hits'] as $hit){
					$revision = $this->getDataService()->getRevisionByEnvironment($hit['_id'], $this->getContentTypeService()->getByName($hit['_type']), $environment);
					$this->getPublishService()->publish($revision, $toEnvironment);
				}
			}
			
 			return $this->redirectToRoute('elasticsearch.search', $requestBis->query->all());
		}
			
	
		
		
		return $this->render( 'publish/publish-search-result.html.twig', [
				'form' => $form->createView(),
				'fromEnvironment' => $environment,
				'contentType' => $contentType,
				'counter' => $total,
		] );	
	}
	
	
}