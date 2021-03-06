<?php

namespace AppBundle\Form\View\Criteria;

use AppBundle\Entity\DataField;
use AppBundle\Entity\View;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * It's the mother class of all specific DataField used in eMS
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
class CriteriaFilterType extends AbstractType {
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);

		if($options['view']){
			/** @var View $view */
			$view = $options['view'];

			$criteriaField = $view->getContentType()->getFieldType();
			if($view->getOptions()['criteriaMode'] == 'internal'){
				$criteriaField = $view->getContentType()->getFieldType()->__get('ems_'.$view->getOptions()['criteriaField']);
			}
			else if ($view->getOptions()['criteriaMode'] == 'another'){
					
			}
			else {
				throw new \Exception('Should never happen');
			}
			
			$choices = [];
			$defaultColumn = false;
			$defaultRow = false;
			
			$fieldPaths = preg_split("/\\r\\n|\\r|\\n/", $view->getOptions()['criteriaFieldPaths']);
			
			foreach ($fieldPaths as $path){
				/**@var \AppBundle\Entity\FieldType $child*/
				$child = $criteriaField->getChildByPath($path);
				if($child) {
					$label = $child->getDisplayOptions()['label']?$child->getDisplayOptions()['label']:$child->getName();
					$choices[$label] = $child->getName();
					$defaultRow = $defaultColumn;
					$defaultColumn = $child->getName();					
				}
			}

			$builder->add('columnCriteria', ChoiceType::class, array(
					'choices'  => $choices,
					'data' => $defaultColumn,
					'attr' => [
							'class' => 'criteria-filter-columnrow'
					]
			));
			
			$builder->add('rowCriteria', ChoiceType::class, array(
					'choices'  => $choices,
					'data' => $defaultRow,
					'attr' => [
							'class' => 'criteria-filter-columnrow'
					],
			));
			
			$builder->add('manage', SubmitEmsType::class, [
					'icon'  => 'fa fa-table',
					'attr' => [
							'class' => 'btn-primary',
					]
			]);
			
			
			
			if($view->getOptions()['categoryFieldPath']){
				$categoryField = $view->getContentType()->getFieldType()->getChildByPath($view->getOptions()['categoryFieldPath']);
				
				if($categoryField) {
					$displayOptions = $categoryField->getDisplayOptions();
					
					$catOptions = $categoryField->getOptions();
					if(isset($catOptions['restrictionOptions']) && isset($catOptions['restrictionOptions']['minimum_role'])){
						$catOptions['restrictionOptions']['minimum_role'] = null;
						$categoryField->setOptions($catOptions);
					}
					$displayOptions['metadata'] = $categoryField;
					$displayOptions['class'] = 'col-md-12';
					$displayOptions['multiple'] = false;
					$displayOptions['required'] = true;
					if(isset($displayOptions['dynamicLoading'])){
						$displayOptions['dynamicLoading'] = false;					
					}
					$builder->add ( 'category', $categoryField->getType(), $displayOptions);
					
				}
			}
			
			
			$criterion = $builder->create('criterion', FormType::class, [
					'label' => ' ',
			]);

			$fieldPaths = preg_split("/\\r\\n|\\r|\\n/", $view->getOptions()['criteriaFieldPaths']);
			
			foreach ($fieldPaths as $path){
				/**@var \AppBundle\Entity\FieldType $child*/
				$child = $criteriaField->getChildByPath($path);
				if($child) {

					$childOptions = $child->getOptions();
					if(isset($childOptions['restrictionOptions']) && isset($childOptions['restrictionOptions']['minimum_role'])){
						$childOptions['restrictionOptions']['minimum_role'] = null;
						$child->setOptions($childOptions);
					}
					
					$displayOptions = $child->getDisplayOptions();
					$displayOptions['metadata'] = $child;
					$displayOptions['class'] = 'col-md-12';
					if(isset($displayOptions['dynamicLoading'])){
						$displayOptions['dynamicLoading'] = false;					
					}
					$displayOptions['attr'] =
						[
								'data-name' => $child->getName()
						];
					
					$displayOptions['multiple'] = true;//($child->getName() == $defaultRow || $child->getName() == $defaultColumn);

					$criterion->add ( $child->getName(), $child->getType(), $displayOptions);	
				}
			}
			
			$builder->add($criterion);
			
			
		}
		
		
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );
		$resolver->setDefault ( 'view', null );
	}
	
	
}