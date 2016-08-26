<?php
namespace AppBundle\Controller\Views;

use AppBundle;
use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Entity\Form\CriteriaUpdateConfig;
use AppBundle\Entity\Revision;
use AppBundle\Entity\View;
use AppBundle\Form\DataField\DataFieldType;
use AppBundle\Form\Factory\ObjectChoiceListFactory;
use AppBundle\Form\Field\ObjectChoiceListItem;
use AppBundle\Form\View\Criteria\CriteriaFilterType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\RevisionRepository;
use AppBundle\Service\DataService;
use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use AppBundle\Exception\LockedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class CriteriaController extends AppController
{
	/**
	 * @Route("/views/criteria/align/{view}", name="views.criteria.align"))
	 * @Method({"POST"})
	 */
	public function alignAction(View $view, Request $request)
	{
		/**@var DataService $dataService*/
		$this->dataService = $this->get('ems.service.data');
				
		$criteriaUpdateConfig = new CriteriaUpdateConfig($view);
		$form = $this->createForm(CriteriaFilterType::class, $criteriaUpdateConfig, [
				'view' => $view,
				'hidden' => true,
		]);
		
		$form->handleRequest($request);
		/** @var CriteriaUpdateConfig $criteriaUpdateConfig */
		$criteriaUpdateConfig = $form->getData();
		
		$tables = $this->generateCriteriaTable($view, $criteriaUpdateConfig);
		$params = explode(':', $request->request->all()['alignOn']);
		
		$isRowAlign = ($params[0]=='row');
		$key = $params[1].':'.$params[2];
		
		$filters = [];
		$criteriaField = $view->getOptions()['criteriaField'];	
		foreach ($tables['criteriaChoiceLists'] as $name => $criteria) {
			if(count($criteria) == 1) {
				$filters[$name] = array_keys($criteria)[0];
			}
		}
		
		$itemToFinalize = [];
		
		foreach ($tables['table'] as $rowId => $row){
			foreach ($row as $colId => $col){
				$alignWith = $tables['table'][$isRowAlign?$key:$rowId][$isRowAlign?$colId:$key];
				if(!empty($col)) {
					/**@var ObjectChoiceListItem $toremove*/
					foreach ($col as $toremove){
						$found = false;
						/**@var ObjectChoiceListItem $item*/
						if(!empty($alignWith)) {
							foreach ($alignWith as $item){
								if($item->getValue() == $toremove->getValue()){
									$found = true;
									break;
								}
							}
						}
						if(!$found) {
							$filters[$criteriaUpdateConfig->getRowCriteria()] = $rowId;
							$filters[$criteriaUpdateConfig->getColumnCriteria()] = $colId;
							
							if(isset($itemToFinalize[$toremove->getValue()])) {
								$revision = $itemToFinalize[$toremove->getValue()];
							}
							else {
								$structuredTarget = explode(":", $toremove->getValue());
								$type = $structuredTarget[0];
								$ouuid = $structuredTarget[1];
								
								/**@var Revision $revision*/
								$revision = $this->dataService->getNewestRevision($type, $ouuid);							
							}
							
							if($revision = $this->removeCriteria($filters, $revision, $criteriaField)) {
								$itemToFinalize[$toremove->getValue()] = $revision;
							}
						}
					}
				}
				if(!empty($alignWith)) {
					/**@var ObjectChoiceListItem $toadd*/
					foreach ($alignWith as $toadd){
						$found = false;
						/**@var ObjectChoiceListItem $item*/
						if(!empty($col)) {
							foreach ($col as $item){
								if($item->getValue() == $toadd->getValue()){
									$found = true;
									break;	
								}
							}							
						}
						if(!$found) {
							$filters[$criteriaUpdateConfig->getRowCriteria()] = $rowId;
							$filters[$criteriaUpdateConfig->getColumnCriteria()] = $colId;
							
							if(isset($itemToFinalize[$toadd->getValue()])) {
								$revision = $itemToFinalize[$toadd->getValue()];
							}
							else {
								$structuredTarget = explode(":", $toadd->getValue());
								$type = $structuredTarget[0];
								$ouuid = $structuredTarget[1];
								
								/**@var Revision $revision*/
								$revision = $this->dataService->getNewestRevision($type, $ouuid);							
							}
							
							if($revision = $this->addCriteria($filters, $revision, $criteriaField)) {
								$itemToFinalize[$toadd->getValue()] = $revision;
							}
						}
					}
					
				}
			}
		}
		
		foreach ($itemToFinalize as $revision) {
			$this->dataService->finalizeDraft($revision);
		}

		sleep(2);
		return $this->redirect($request->request->all()['source_url']);
	}
	
	private function isAuthorized(FieldType $criteriaField) {
		/**@var AuthorizationChecker $security*/
		$security = $this->get('security.authorization_checker');
		
		$authorized = $security->isGranted($criteriaField->getMinimumRole());
		if($authorized) {
			foreach ($criteriaField->getChildren() as $child){
				$authorized = $security->isGranted($criteriaField->getMinimumRole());
				if(!$authorized){
					break;
				}
			}
		}
		return $authorized;
	}
	
	/**
	 * @Route("/views/criteria/table/{view}", name="views.criteria.table"))
     * @Method({"GET"})
	 */
	public function generateCriteriaTableAction(View $view, Request $request)
	{

		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var RevisionRepository $revisionRep */
		$revisionRep = $em->getRepository('AppBundle:Revision');
		$counters = $revisionRep->draftCounterGroupedByContentType();
		
		foreach ($counters as $counter){
			if($counter['content_type_id'] == $view->getContentType()->getId()) {
				$this->addFlash('warning', 'There is '.$counter['counter'].' drafts, you wont be able to update them from here.');
			}
		}
		
		
		$criteriaUpdateConfig = new CriteriaUpdateConfig($view);
		
		$form = $this->createForm(CriteriaFilterType::class, $criteriaUpdateConfig, [
				'view' => $view,
				'method' => 'GET',
		]);
		
		$form->handleRequest($request);
		/** @var CriteriaUpdateConfig $criteriaUpdateConfig */
		$criteriaUpdateConfig = $form->getData();
			
		$contentType = $view->getContentType();
		$criteriaFieldName = $view->getOptions()['criteriaField'];
		$criteriaField = $contentType->getFieldType()->__get('ems_'.$criteriaFieldName);
		/** @var \AppBundle\Entity\FieldType $columnField */
		$columnField = $criteriaField->__get('ems_'.$criteriaUpdateConfig->getColumnCriteria());	
		/** @var \AppBundle\Entity\FieldType $rowField */
		$rowField = $criteriaField->__get('ems_'.$criteriaUpdateConfig->getRowCriteria());
		
		$authorized = $this->isAuthorized($criteriaField);
		if($authorized) {
			$hiddenform = $this->createForm(CriteriaFilterType::class, $criteriaUpdateConfig, [
					'view' => $view,
					'hidden' => true,
					'action' => $this->get('router')->generate('views.criteria.align', ['view' => $view->getId()]),
					'attr' => [
							'id' =>  'hiddenFilterForm'
					]
			]);
		}
		else {
			$hiddenform = NULL;
		}
		
		$tables = $this->generateCriteriaTable($view, $criteriaUpdateConfig);
		
		return $this->render( 'view/custom/criteria_table.html.twig',[
			'table' => $tables['table'],
			'rowFieldType' => $rowField,
			'columnFieldType' => $columnField,
			'config' => $criteriaUpdateConfig,
			'columns' => $tables['criteriaChoiceLists'][$criteriaUpdateConfig->getColumnCriteria()],
			'rows' => $tables['criteriaChoiceLists'][$criteriaUpdateConfig->getRowCriteria()],
			'criteriaChoiceLists' => $tables['criteriaChoiceLists'],
			'view' => $view,
			'categoryChoiceList' => $tables['categoryChoiceList'],
			'authorized' => $authorized,
			'hiddenForm' => $hiddenform->createView(),
		]);
	}
	
	
	public function generateCriteriaTable(View $view, CriteriaUpdateConfig $criteriaUpdateConfig)
	{		
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
			$choicesList = $dataFieldType->getChoiceList($criteria->getFieldType(), $criteria->getRawData());
			$criteriaChoiceLists[$criteria->getFieldType()->getName()] = $choicesList;
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
		
		return [
			'table' => $table,
			'criteriaChoiceLists' => $criteriaChoiceLists,
			'categoryChoiceList' => $categoryChoiceList,
		];
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
		
		//TODO securtity test
		
		/**@var DataService $dataService*/
		$this->dataService = $this->get('ems.service.data');
		
		$structuredTarget = explode(":", $target);
		
		$type = $structuredTarget[0];
		$ouuid = $structuredTarget[1];
		
		/**@var Session $session */
		$session = $this->get('session');
		
		/**@var Revision $revision*/
		$revision = $this->dataService->getNewestRevision($type, $ouuid);	
		
		if($revision->getDraft()) {
			$this->addFlash('warning', 'Impossible to update '.$revision. ' has there is a draft in progress');
			return $this->render( 'ajax/notification.json.twig', [
					'success' => false,
			] );
		}


		try {
			
			if($revision = $this->addCriteria($filters, $revision, $criteriaField)){
				$this->dataService->finalizeDraft($revision);
			}

		} catch (LockedException $e) {
			$this->addFlash('warning', 'Impossible to update '.$revision. ' has the revision is locked by '.$revision->getLockBy());
			return $this->render( 'ajax/notification.json.twig', [
					'success' => false,
			] );
		}
		return $this->render( 'ajax/notification.json.twig', [
				'success' => true,
		] );
	}
		
	public function addCriteria($filters, Revision $revision, $criteriaField)
	{		
		
		$rawData = $revision->getRawData();
		if(!isset($rawData[$criteriaField])) {
			$rawData[$criteriaField] = [];
		}
		$multipleField = $this->getMultipleField($revision->getContentType()->getFieldType()->__get('ems_'.$criteriaField));
		
		$found = false;
		foreach ($rawData[$criteriaField] as &$criteriaSet) {
			$found = true;
			foreach ($filters as $criterion => $value) {
				if($criterion != $multipleField && $value != $criteriaSet[$criterion] ){
					$found = false;
					break;
				}
			}
			if($found){
				
				if($multipleField && FALSE === array_search($filters[$multipleField], $criteriaSet[$multipleField]) ){
					$criteriaSet[$multipleField][] = $filters[$multipleField];
					if(!$revision->getDraft()){
						$revision = $this->dataService->initNewDraft($revision->getContentType()->getName(), $revision->getOuuid(), $revision);
					}
					$revision->setRawData($rawData);
					$this->addFlash('notice', '<b>Added</b> '.$multipleField.' with value '.$filters[$multipleField].' to '.$revision);
					return $revision;
				}
				else{
					$this->addFlash('notice', 'Criteria already existing for '.$revision);
				}
				break;
			}
		}
		
		if(!$found){
			$newCriterion = [];
			foreach ($filters as $criterion => $value) {
				if($criterion == $multipleField){
					$newCriterion[$criterion] = [$value];
				}
				else {
					$newCriterion[$criterion] = $value;
				}
			}
			$rawData[$criteriaField][] = $newCriterion;
			if(!$revision->getDraft()){
				$revision = $this->dataService->initNewDraft($revision->getContentType()->getName(), $revision->getOuuid(), $revision);
			}
			$revision->setRawData($rawData);
			
			$this->addFlash('notice', '<b>Added</b> to '.$revision);
			return $revision;
		}
		return false;
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
		

		//TODO securtity test
		
		/**@var DataService $dataService*/
		$this->dataService = $this->get('ems.service.data');
		
		$structuredTarget = explode(":", $target);
		
		$type = $structuredTarget[0];
		$ouuid = $structuredTarget[1];
		
		/**@var Session $session */
		$session = $this->get('session');
		
		/**@var Revision $revision*/
		$revision = $this->dataService->getNewestRevision($type, $ouuid);
		
		if($revision->getDraft()) {
			$this->addFlash('warning', 'Impossible to update '.$revision. ' has there is a draft in progress');
			return $this->render( 'ajax/notification.json.twig', [
					'success' => false,
			] );
		}

		try {
			if($revision = $this->removeCriteria($filters, $revision, $criteriaField)){
				$this->dataService->finalizeDraft($revision);				
			}


		} catch (LockedException $e) {
			$this->addFlash('warning', 'Impossible to update '.$revision. ' has the revision is locked by '.$revision->getLockBy());
			return $this->render( 'ajax/notification.json.twig', [
					'success' => false,
			] );
		}		
		
		return $this->render( 'ajax/notification.json.twig', [
			'success' => true,
		] );
	}
		
	public function removeCriteria($filters, Revision $revision, $criteriaField)
	{		
		
		$rawData = $revision->getRawData();
		if(!isset($rawData[$criteriaField])) {
			$rawData[$criteriaField] = [];
		}
		$criteriaFieldType = $revision->getContentType()->getFieldType()->__get('ems_'.$criteriaField);
		$multipleField = $this->getMultipleField($criteriaFieldType);
		
		$found = false;
		foreach ($rawData[$criteriaField] as $index => $criteriaSet) {
			$found = true;
			foreach ($filters as $criterion => $value) {
				if($criterion != $multipleField && $value != $criteriaSet[$criterion] ){
					$found = false;
					break;
				}
			}
			if($found){
				
				if($multipleField){
					$indexKey = array_search($filters[$multipleField], $criteriaSet[$multipleField]);
					if($indexKey === FALSE){
						$this->addFlash('notice', 'Criteria not found in multiple key');
					}
					else {
						unset($rawData[$criteriaField][$index][$multipleField][$indexKey]);
						$rawData[$criteriaField][$index][$multipleField] = array_values($rawData[$criteriaField][$index][$multipleField]);
						if(count($rawData[$criteriaField][$index][$multipleField]) == 0){
							unset($rawData[$criteriaField][$index]);
							$rawData[$criteriaField] = array_values($rawData[$criteriaField]);
						}

						if(!$revision->getDraft()){
							$revision = $this->dataService->initNewDraft($revision->getContentType()->getName(), $revision->getOuuid(), $revision);							
						}
						$revision->setRawData($rawData);
						$this->addFlash('notice', '<b>Remove</b> '.$multipleField.' with value '.$filters[$multipleField].' from '.$revision);
						return $revision;
					}
				}
				else{
					unset($rawData[$criteriaField][$index]);
					$rawData[$criteriaField][$index] = array_values($rawData[$criteriaField][$index]);
					
					if(!$revision->getDraft()){
						$revision = $this->dataService->initNewDraft($revision->getContentType()->getName(), $revision->getOuuid(), $revision);
					}
					$revision->setRawData($rawData);
					$this->addFlash('notice', '<b>Remove</b> from '.$revision);	
					return $revision;
				}
				break;
			}
		}
		
		if(!$found){
			$this->addFlash('notice', 'Criteria not found for '.$revision);
		}
		return false;
	}
	
	private function addToTable(ObjectChoiceListItem &$choice, array &$table, array &$criterion, array $criteriaNames, array &$criteriaChoiceLists, CriteriaUpdateConfig &$config, array $context = []){
		$criteriaName = array_pop($criteriaNames);
		$criterionList = $criterion[$criteriaName];
		if(! is_array($criterionList)){
			$criterionList = [$criterionList];
		}
		foreach ($criterionList as $value) {
			if(isset($criteriaChoiceLists[$criteriaName][$value])){
				$context[$criteriaName] = $value;
				if(count($criteriaNames) > 0){
					//let see (recursively) if the other criterion applies to find a matching context
					$this->addToTable($choice, $table, $criterion, $criteriaNames, $criteriaChoiceLists, $config, $context);
				}
				else{
					//all criterion apply the current choice can be added to the table depending the context
					if(!isset($table[$context[$config->getRowCriteria()]][$context[$config->getColumnCriteria()]])) {
						$table[$context[$config->getRowCriteria()]][$context[$config->getColumnCriteria()]] = [];
					}
					$table[$context[$config->getRowCriteria()]][$context[$config->getColumnCriteria()]][] = $choice;
				}
			}
		}
		
	}
	
	private function getMultipleField(FieldType $criteriaFieldType){
		foreach ($criteriaFieldType->getChildren() as $criteria){
			if(isset($criteria->getDisplayOptions()['multiple']) && $criteria->getDisplayOptions()['multiple']){
				return $criteria->getName();
			}
		}
		return false;
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