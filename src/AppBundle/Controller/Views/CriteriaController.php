<?php
namespace AppBundle\Controller\Views;

use AppBundle;
use AppBundle\Controller\ContentManagement\DataController;
use AppBundle\Entity\ContentType;
use AppBundle\Entity\View;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\FieldTypeRepository;
use AppBundle\Repository\ViewRepository;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Repository\RevisionRepository;
use AppBundle\Entity\DataField;
use AppBundle\Form\DataField\CollectionItemFieldType;

class CriteriaController extends DataController
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
		

		/** @var ViewRepository $viewRepository */
		$viewRepository = $em->getRepository ( 'AppBundle:View' );
		
		/** @var View $view */
		$view = $viewRepository->find ( $request->query->get('viewId'));
		
		/** @var Client $client */
		$client = $this->getElasticsearch();
		
		
		$criteriaField = $view->getOptions()['criteriaField'];
		
		$body = [
			'query' => [
				'nested' => [
						'path' => $criteriaField,
						'query' => [
							'and' => [
									
							]
						]
						
				]
			] 
		];

		/** @var FieldTypeRepository $fieldTypeRepository */
		$fieldTypeRepository = $em->getRepository ( 'AppBundle:FieldType' );
		$criteriaFilters = [];
		
		$criterionRequest = $request->query->get('criterion', []);
		
		foreach ($criterionRequest as $criteria){
			/** @var \AppBundle\Entity\FieldType $fieldType */
			$fieldType = $fieldTypeRepository->find($criteria['field']);
			
			$criteriaFilters[] = [
					'id' => $fieldType->getId(),
					'name' => $fieldType->getName(),
					'value' => isset($criteria['filters'])?$criteria['filters'][0]:null,
			];
			
			if(isset($criteria['filters'])){
				if(count($criteria['filters']) > 1){
					$subquery = [
						"or" => []	
					];
					foreach ($criteria['filters'] as $filter){
						$subquery['or'][] = [
							'term' => [
								$view->getOptions()['criteriaField'].'.'.$fieldType->getName() => [
									'value' => $filter
								]
							]
						];
					}
				}
				else {
					$subquery = [
						'term' => [
							$view->getOptions()['criteriaField'].'.'.$fieldType->getName() => [
								'value' => $criteria['filters'][0]
							]
						]
					];
				}
				
				$body['query']['nested']['query']['and'][] = $subquery;	
				
			}
		}
		

		$column = end($criterionRequest);
		$row = prev($criterionRequest);
		
		/** @var \AppBundle\Entity\FieldType $columnField */
		$columnField = $fieldTypeRepository->find($column['field']);
		if( !isset($column['filters']) || count($column['filters']) == 0 ){
			
			$columns = $columnField->getDisplayOptions()['choices'];
			$columns = explode("\n", str_replace("\r", "", $columns));
		}
		else{
			$columns = $column['filters'];
		}

		/** @var \AppBundle\Entity\FieldType $rowField */
		$rowField = $fieldTypeRepository->find($row['field']);			
		if( !isset($row['filters']) || count($row['filters']) == 0 ){
			
			$rows = $rowField->getDisplayOptions()['choices'];
			$rows = explode("\n", str_replace("\r", "", $rows));
		}
		else{
			$rows = $row['filters'];
		}
		
		
		
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
			foreach ($item['_source'][$criteriaField] as $criterion){
				$relevant = true;
				foreach ($criterionRequest as $filter){
					if(isset($filter['filters']) && count($filter['filters']) > 0){
						$criterionName = $fieldTypeRepository->find($filter['field'])->getName();
						if( ! in_array($criterion[$criterionName], $filter['filters'])){
							//dump($criterion[$criterionName]." not in so not relevant");
							$relevant = false;
							break;						
						}
					}
				}
				if($relevant){
					$rowIdx = $criterion[$rowField->getName()];
					$columnIdx = $criterion[$columnField->getName()];
					if(! isset($table[$rowIdx][$columnIdx])){
						$table[$rowIdx][$columnIdx]= [];
					}
					$value = $item['_type'].':'.$item['_id'];
					if( $contentType->getLabelField() && $item['_source'][$contentType->getLabelField()]){
						$label = $item['_source'][$contentType->getLabelField()];
					}
					else {
						$label = $value;
					}
					
					$color;
					if($contentType->getColorField() && $item['_source'][$contentType->getColorField()]){
						$color = $item['_source'][$contentType->getColorField()];
					}
					
					$table[$rowIdx][$columnIdx][] = [
						'label' => $label,
						'value' => $value,
						'color' => $color,
					];
					
				}
			}
			
		}

		//remove the row and the column not needed in the twig as they are specific to each cell
		array_pop($criteriaFilters);
		array_pop($criteriaFilters);
		
		return $this->render( 'view/custom/criteria_table.html.twig',[
			'table' => $table,
			'criterion' => $request->query->get('criterion', []),
			'rowFieldType' => $rowField,
			'columnFieldType' => $columnField,
			'criteriaFilters' => $criteriaFilters
		]);
	}
	
	
	/**
	 *
	 * @Route("/views/criteria/addCriterion", name="views.criteria.add"))
     * @Method({"POST"})
	 */
	public function addCriteriaAction(Request $request)
	{
		$filters = $request->request->get('filters');
		$target = $request->request->get('target');
		$criteriaField = $request->request->get('criteriaField');
		
		$structuredTarget = explode(":", $target);
		
		$type = $structuredTarget[0];
		$ouuid = $structuredTarget[1];
		
		$revision = $this->initNewDraft($type, $ouuid);
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
		
		$criteriaField = $revision->getDataField()->__get('ems_'.$criteriaField);	
		if(! $this->findCriterion($criteriaField, $filters)){
			$filedType = clone $criteriaField->getFieldType();
			$filedType->setType(CollectionItemFieldType::class);
			$newDataField = new DataField();
			$newDataField->setRevisionId($revision->getId());
			$newDataField->setOrderKey($criteriaField->getChildren()->count());
			$newDataField->setParent($criteriaField);
			$newDataField->setFieldType($filedType);
			$criteriaField->addChild($newDataField);
				
			$newDataField->updateDataStructure($filedType);
			
			foreach ($filters as $filter){
				$newDataField->__get('ems_'.$filter['name'])->setTextValue($filter['value']);
			}
			
			$newDataField->setFieldType(null);
			
			$this->finalizeDraft($revision);
			
		}
		else{
			$this->discardDraft($revision);
		}
		
		
		return new Response(json_encode([]));
	}
	
	
	/**
	 *
	 * @Route("/views/criteria/removeCriterion", name="views.criteria.remove"))
     * @Method({"POST"})
	 */
	public function removeCriteriaAction(Request $request)
	{
		$filters = $request->request->get('filters');
		$target = $request->request->get('target');
		$criteriaField = $request->request->get('criteriaField');
		
		$structuredTarget = explode(":", $target);
		
		$type = $structuredTarget[0];
		$ouuid = $structuredTarget[1];
		
		$revision = $this->initNewDraft($type, $ouuid);

		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		
		/** @var RevisionRepository $repository */
		$repository = $em->getRepository('AppBundle:Revision');
		
		$criteriaField = $revision->getDataField()->__get('ems_'.$criteriaField);
		
		while($child = $this->findCriterion($criteriaField, $filters)){
			$criteriaField->removeChild($child);		
		}

		$this->finalizeDraft($revision);
		
		return new Response(json_encode([]));
	}
	
	private function findCriterion(DataField $criteriaField, $filters){
		/** @var DataField $child */
		foreach ($criteriaField->getChildren() as $child){
			$found = true;
			foreach($filters as $filter) {
				if(strcmp( $filter['value'], $child->__get('ems_'.$filter['name'])->getTextValue() ) != 0){
					$found = false;
					break;
				}
			}
			if($found){
				return $child;
			}
		}	
		return false;
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
		$repository = $em->getRepository ( 'AppBundle:FieldType' );
		
		
		/** @var \AppBundle\Entity\FieldType $field */
		$field = $repository->find ( $request->query->get('targetField'));
		

		$choices = $field->getDisplayOptions()['choices'];
		$choices = explode("\n", str_replace("\r", "", $choices));
		$labels = $field->getDisplayOptions()['labels'];
		$labels = explode("\n", str_replace("\r", "", $labels));
		
		$out = [
			'incomplete_results' => false,
			'total_count' => count($choices),
			'items' => []
		];
		
		foreach ($choices as $idx => $choice) {
			$label = isset($labels[$idx])?$labels[$idx]:$choice;
			if( !$request->query->get('q') || stristr($choice, $request->query->get('q')) || stristr($label, $request->query->get('q'))) {
				$out['items'][] = [
						'id' => $choice,
						'text' => $label,
				];
			}
		}
		
		return new Response(json_encode($out));
	}
}