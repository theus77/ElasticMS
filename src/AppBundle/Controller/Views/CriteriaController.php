<?php
namespace AppBundle\Controller\Views;

use AppBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\ContentType;
use AppBundle\Repository\ContentTypeRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Elasticsearch\Client;
use AppBundle\Controller\AppController;

class CriteriaController extends AppController
{
	/**
	 *
	 * @Route("/views/criteria/table", name="views.criteria.table"))
	 */
	public function generateCriteriaTableAction(Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:ContentType' );
		
		/** @var ContentType $contentType */
		$contentType = $repository->find ( $request->query->get('contentTypeId'));
		
		/** @var Client $client */
		$client = $this->getElasticsearch();
		
		$body = [
			'query' => [
				'and' => [
						
				]
			] 
		];
		
		$row = null;
		$column = null;
		
		foreach ($request->query->get('criterion', []) as $criteria){
			if(isset($criteria['filter'])){
				$body['query']['and'][] = [
					'term' => [
						$criteria['field'] => [
							'value' => $criteria['filter']
						]
					]
				];		
			}
			else{
				if($row){
					$column = $criteria['field'];
				}
				else{
					$row = $criteria['field'];
				}
			}
			
		}
		

		/** @var \AppBundle\Entity\FieldType $columnField */
		$columnField = $contentType->getFieldType()->__get($column);
		/** @var \AppBundle\Entity\FieldType $rowField */
		$rowField = $contentType->getFieldType()->__get($row);
		
		$columns = $columnField->getDisplayOptions()['choices'];
		$columns = explode("\n", str_replace("\r", "", $columns));
		
		$rows = $rowField->getDisplayOptions()['choices'];
		$rows = explode("\n", str_replace("\r", "", $rows));
		
		$table = [];
		foreach($rows as $rowItem){
			$table[$rowItem] = [];
			foreach ($columns as $columnItem){
				$table[$rowItem][$columnItem] = null;
			}
		}
		
		
		$result = $client->search([
			'index' => $contentType->getEnvironment()->getAlias(),
			'type' => $contentType->getName(),
			'body' => $body
		]);
		
		
		foreach ($result['hits']['hits'] as $item){
			$table[$item['_source'][$row]][$item['_source'][$column]] = $item['_source']['coming_card'];
		}
		
// 		dump($table); exit;
		return $this->render( 'view/custom/criteria_table.html.twig',[
			'table' => $table,
			'criterion' => $request->query->get('criterion', []),
			'row' => $row,
			'column' => $column,
		]);
	}
	
	/**
	 *
	 * @Route("/views/criteria/fieldFilter", name="views.criteria.fieldFilter"))
	 */
	public function fieldFilterAction(Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var ContentTypeRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:ContentType' );
		
		/** @var ContentType $contentType */
		$contentType = $repository->find ( $request->query->get('contentTypeId'));
		
		/** @var \AppBundle\Entity\FieldType $field */
		$field = $contentType->getFieldType()->__get($request->query->get('targetField'));
		$choices = $field->getDisplayOptions()['choices'];
		$choices = explode("\n", str_replace("\r", "", $choices));
		
		$out = [
			'incomplete_results' => false,
			'total_count' => count($choices),
			'items' => []
		];
		foreach ($choices as $choice){
			$out['items'][] = [
					'id' => $choice,
					'text' => $choice,
			];
		}
		
		return new Response(json_encode($out));
	}
}