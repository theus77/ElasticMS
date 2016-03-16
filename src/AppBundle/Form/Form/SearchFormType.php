<?php

namespace AppBundle\Form\Form;

use AppBundle\Entity\Revision;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use AppBundle\Form\Subform\SearchFilterType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

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
						'class' => 'btn-primary' 
				],
				'icon' => 'fa fa-search'
		]);
	}
	
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
				'data_class' => 'AppBundle\Entity\Form\Search',
		]);
	}
}