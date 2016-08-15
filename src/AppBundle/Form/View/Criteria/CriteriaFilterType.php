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
		
// 		dump($options);

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
			
			
			
			if($view->getContentType()->getCategoryField()){
				$categoryField = $view->getContentType()->getFieldType()->__get('ems_'.$view->getContentType()->getCategoryField());
				$displayOptions = $categoryField->getDisplayOptions();
				
				$options = $categoryField->getOptions();
				if(isset($options['restrictionOptions']) && isset($options['restrictionOptions']['minimum_role'])){
					$options['restrictionOptions']['minimum_role'] = null;
					$categoryField->setOptions($options);
				}
				$displayOptions['metadata'] = $categoryField;
				$displayOptions['class'] = 'col-md-12';
				$displayOptions['multiple'] = false;
				$displayOptions['required'] = true;
				if(isset($displayOptions['dynamicLoading'])){
					$displayOptions['dynamicLoading'] = false;					
				}
				$builder
					->add ( 'category', $categoryField->getType(), $displayOptions);
			}
			
			
			$criterion = $builder->create('criterion', FormType::class);

			/**@var \AppBundle\Entity\FieldType $child*/
			foreach ($criteriaField->getChildren() as $child){
				if(!$child->getDeleted()) {

					$options = $child->getOptions();
					if(isset($options['restrictionOptions']) && isset($options['restrictionOptions']['minimum_role'])){
						$options['restrictionOptions']['minimum_role'] = null;
						$child->setOptions($options);
					}
					
					$displayOptions = $child->getDisplayOptions();
	// 				dump($displayOptions);
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
					$criterion
							->add ( $child->getName(), $child->getType(), $displayOptions);			
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