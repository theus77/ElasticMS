<?php
namespace AppBundle\Controller\Views;

use AppBundle;
use AppBundle\Controller\ContentManagement\DataController;
use AppBundle\Entity\ContentType;
use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Entity\Form\CriteriaUpdateConfig;
use AppBundle\Entity\View;
use AppBundle\Form\DataField\CollectionItemFieldType;
use AppBundle\Form\DataField\DataFieldType;
use AppBundle\Form\Factory\ObjectChoiceListFactory;
use AppBundle\Form\Field\ObjectChoiceListItem;
use AppBundle\Form\View\Criteria\CriteriaFilterType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\RevisionRepository;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CriteriaController extends DataController
{
	/**
	 *
	 * @Route("/views/criteria/table/{view}", name="views.criteria.table"))
	 */
	public function generateCriteriaTableAction(View $view, Request $request)
	{
		$criteriaUpdateConfig = new CriteriaUpdateConfig($view);
		
		$form = $this->createForm(CriteriaFilterType::class, $criteriaUpdateConfig, [
				'view' => $view,
				'method' => 'GET',
		]);
		
		$criteriaUpdateConfig = $form->getData();
		
		$form->handleRequest($request);
		
		/** @var CriteriaUpdateConfig $criteriaUpdateConfig */
		$criteriaUpdateConfig = $form->getData();
		
		/** @var Client $client */
		$client = $this->getElasticsearch();
		
		$contentType = $view->getContentType();
		$criteriaFieldName = $view->getOptions()['criteriaField'];
		$criteriaField = $contentType->getFieldType()->__get('ems_'.$criteriaFieldName);
		
		
		$body = [
				'query' => [
						'bool' => [	
								'must' => [
								]
						]
				]
		];			
		
		$categoryChoiceList = false;
		if($criteriaUpdateConfig->getCategory()){
			$dataField = $criteriaUpdateConfig->getCategory();
			if($dataField->getRawData() && strlen($dataField->getRawData()) > 0){
				$categoryFieldTypeName = $dataField->getFieldType()->getType();
				/**@var DataFieldType $categoryFieldType */
				$categoryFieldType = $this->get('form.registry')->getType($categoryFieldTypeName)->getInnerType();
			
				$body['query']['bool']['must'][] = $categoryFieldType->getElasticsearchQuery($dataField);
				$categoryChoiceList  = $categoryFieldType->getChoiceList($dataField->getFieldType(), [$dataField->getRawData()]);				
			}
			
		}
		
		
		$criteriaFilters = [];
		$criteriaChoiceLists = [];
		/** @var DataField $criteria */
		foreach ($criteriaUpdateConfig->getCriterion() as $idxName => $criteria){
			$fieldTypeName = $criteria->getFieldType()->getType();
			//TODO: the 2 next lignes should replace all new $typeName everywhere!!!!!
			/**@var DataFieldType $dataFieldType */
			$dataFieldType = $this->get('form.registry')->getType($fieldTypeName)->getInnerType();
			if(count($criteria->getRawData()) > 0) {
				$criteriaFilters[] = $dataFieldType->getElasticsearchQuery($criteria, ['nested' => $criteriaFieldName]);				
			}
			$criteriaChoiceLists[$criteria->getFieldType()->getName()] = $dataFieldType->getChoiceList($criteria->getFieldType(), $criteria->getRawData());
		}
		

		$body['query']['bool']['must'][] = [
			'nested' => [
				'path' => $criteriaFieldName,
				'query' => [
						'bool' => ['must' => $criteriaFilters] 
				]
			]				
		];
		
		/** @var \AppBundle\Entity\FieldType $columnField */
		$columnField = $criteriaField->__get('ems_'.$criteriaUpdateConfig->getColumnCriteria());
		

		/** @var \AppBundle\Entity\FieldType $rowField */
		$rowField = $criteriaField->__get('ems_'.$criteriaUpdateConfig->getRowCriteria());
		
		
		$table = [];
		/**@var ObjectChoiceListItem $rowItem*/
		foreach($criteriaChoiceLists[$criteriaUpdateConfig->getRowCriteria()] as $rowItem){
			$table[$rowItem->getValue()] = [];
			/**@var ObjectChoiceListItem $columnItem*/
			foreach ($criteriaChoiceLists[$criteriaUpdateConfig->getColumnCriteria()] as $columnItem){
				$table[$rowItem->getValue()][$columnItem->getValue()] = null;
			}
		}
		
		$result = $client->search([
			'index' => $contentType->getEnvironment()->getAlias(),
			'type' => $contentType->getName(),
			'body' => $body
		]);

		/**@var ObjectChoiceListFactory $objectChoiceListFactory*/
		$objectChoiceListFactory = $this->get('ems.form.factories.objectChoiceListFactory');
		$loader = $objectChoiceListFactory->createLoader($view->getContentType()->getName(), false);
		
		foreach ($result['hits']['hits'] as $item){
			$value = $item['_type'].':'.$item['_id'];
			$choice = $loader->loadChoiceList()->loadChoices([$value])[$value];
			
			foreach ($item['_source'][$criteriaFieldName] as $criterion){
				$this->addToTable($choice, $table, $criterion, array_keys($criteriaChoiceLists), $criteriaChoiceLists, $criteriaUpdateConfig);
			}
			
		}
		
		return $this->render( 'view/custom/criteria_table.html.twig',[
			'table' => $table,
			'rowFieldType' => $rowField,
			'columnFieldType' => $columnField,
			'config' => $criteriaUpdateConfig,
			'columns' => $criteriaChoiceLists[$criteriaUpdateConfig->getColumnCriteria()],
			'rows' => $criteriaChoiceLists[$criteriaUpdateConfig->getRowCriteria()],
			'criteriaChoiceLists' => $criteriaChoiceLists,
			'view' => $view,
			'categoryChoiceList' => $categoryChoiceList,
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
	
	private function addToTable(ObjectChoiceListItem &$choice, array &$table, array &$criterion, array $criteriaNames, array &$criteriaChoiceLists, CriteriaUpdateConfig &$config, array $context = []){
		$criteriaName = array_pop($criteriaNames);
		foreach ($criterion[$criteriaName] as $value) {
			if(isset($criteriaChoiceLists[$criteriaName][$value])){
				$context[$criteriaName] = $value;
// 				dump($value);
				if(count($criteriaNames) > 0){
					//let see (recursively) if the other criterion applies to find a matching context
					$this->addToTable($choice, $table, $criterion, $criteriaNames, $criteriaChoiceLists, $config, $context);
				}
				else{
					//all criterion apply the current choice can be added to the table depending the context
// 					dump($context);
					if(!isset($table[$context[$config->getRowCriteria()]][$context[$config->getColumnCriteria()]])) {
						$table[$context[$config->getRowCriteria()]][$context[$config->getColumnCriteria()]] = [];
					}
					$table[$context[$config->getRowCriteria()]][$context[$config->getColumnCriteria()]][] = $choice;
				}
			}
		}
		
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