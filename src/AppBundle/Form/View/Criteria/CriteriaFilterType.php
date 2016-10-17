<?php

namespace AppBundle\Form\View\Criteria;

use AppBundle\Entity\DataField;
use AppBundle\Entity\View;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use AppBundle\Form\DataField\HiddenFieldType;

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
			$criteriaField = $view->getContentType()->getFieldType()->__get('ems_'.$view->getOptions()['criteriaField']);

			$choices = [];
			$defaultColumn = false;
			$defaultRow = false;
			/**@var \AppBundle\Entity\FieldType $child*/
			foreach ($criteriaField->getChildren() as $child){
				if(!$child->getDeleted()) {
					$label = $child->getDisplayOptions()['label']?$child->getDisplayOptions()['label']:$child->getName();
					$choices[$label] = $child->getName();
					$defaultRow = $defaultColumn;
					$defaultColumn = $child->getName();					
				}
			}

			if($options['hidden']) {
				$builder->add('columnCriteria', HiddenType::class);
				$builder->add('rowCriteria', HiddenType::class);
			}
			else {
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
				
			}
			
			
			
			if($view->getContentType()->getCategoryField()){
				$categoryField = $view->getContentType()->getFieldType()->__get('ems_'.$view->getContentType()->getCategoryField());
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
				if($options['hidden']) {
					$builder->add('category', HiddenFieldType::class, ['metadata' => $categoryField, 'required' => false]);
				}
				else {
					$builder->add ( 'category', $categoryField->getType(), $displayOptions);
				}
			}
			
			
			$criterion = $builder->create('criterion', FormType::class, [
					'label' => ' ',
			]);

			/**@var \AppBundle\Entity\FieldType $child*/
			foreach ($criteriaField->getChildren() as $child){
				if(!$child->getDeleted()) {

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

					if($options['hidden']) {
						$criterion->add ( $child->getName(), HiddenFieldType::class, ['metadata' => $child, 'required' => false]);
					}
					else {
						$criterion->add ( $child->getName(), $child->getType(), $displayOptions);			
					}
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
		$resolver->setDefault ( 'hidden', false );
	}
	
	
}