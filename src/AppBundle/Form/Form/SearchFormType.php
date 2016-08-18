<?php

namespace AppBundle\Form\Form;

use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Form\Subform\SearchFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchFormType extends AbstractType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('boolean', ChoiceType::class, [
				'choices' => [
						'And' => 'and',
						'Or' => 'or',
				],
				'label' => 'Boolean operator'
		]);
		$builder->add('sortBy', TextType::class, [
				'required' => false,
		]);
		$builder->add('sortOrder', ChoiceType::class, [
				'choices' => [
						'Ascending' => 'asc',
						'Descending' => 'desc',
				],
				'required' => false,
		]);
		$builder->add('aliasFacet', HiddenType::class);
		$builder->add('typeFacet', HiddenType::class);
		$builder->add('filters', CollectionType::class, array(
				// each entry in the array will be an "email" field
				'entry_type'   => SearchFilterType::class,
				'allow_add'    => true,
				// these options are passed to each "email" type
// 				'entry_options'  => array(
// 						'required'  => false,
// 						'attr'      => array('class' => 'email-box')
// 				),
		))->add('search', SubmitEmsType::class, [
				'attr' => [ 
						'class' => 'btn-primary btn-md' 
				],
				'icon' => 'fa fa-search'
		])->add('exportResults', SubmitEmsType::class, [
				'attr' => [
						'class' => 'btn-primary btn-sm'
				],
				'icon' => 'glyphicon glyphicon-export',
		]);
		
		if(!$options['savedSearch']){
			$builder->add('save', SubmitEmsType::class, [
					'attr' => [ 
							'class' => 'btn-primary btn-md' 
					],
					'icon' => 'fa fa-save',
			]);
			
		}
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
				'data_class' => 'AppBundle\Entity\Form\Search',
				'savedSearch' => false,
				'csrf_protection' => false,
		]);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		/* give options for twig context */
		parent::buildView ( $view, $form, $options );
	}
}