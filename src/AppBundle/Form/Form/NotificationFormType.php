<?php

namespace AppBundle\Form\Form;

use AppBundle\Form\Field\SubmitEmsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use AppBundle\Entity\Template;
use Doctrine\ORM\EntityRepository;
use AppBundle\Service\EnvironmentService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Form\Field\SelectPickerType;

class NotificationFormType extends AbstractType {
	
	private $circleType;
	//private $choices;
	private $service;
	
	public function __construct($circleType, EnvironmentService $service)
	{
		$this->service = $service;
		$this->circleType = $circleType;
		//$this->choices = null;
	}
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {		
		
		//TODO: why is this here?
		//http://symfony.com/doc/current/cookbook/form/dynamic_form_modification.html#cookbook-dynamic-form-modification-suppressing-form-validation
		//$builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
		//	$event->stopPropagation();
		//}, 900);

		$builder->add('template', EntityType::class, [
			'class' => 'AppBundle:Template',
			'query_builder' => function (EntityRepository $er) {
				return $er->createQueryBuilder('t')
				->where("t.renderOption = 'notification'");
				},
			'choice_label' => function ($value, $key, $index) {
				/**@var Template $value*/
				return '<i class="'.$value->getContentType()->getIcon().' text-'.$value->getContentType()->getColor().'"></i>&nbsp;&nbsp;'.$value->getName().' for '.$value->getContentType()->getSingularName();
			},
			'multiple' => true,
			'required' => false,
			'choice_value' => function ($value) {
			if($value != null){
				return $value->getId();
			}
			return $value;
			},
			'attr' => [
					'class' => 'select2'
			],
		])
		->add('environment', ChoiceType::class, [
				'attr' => [
					'class' => 'select2'
				],
 				'multiple' => true,
				'choices' => $this->service->getAll(),
				'required' => false,
				'choice_label' => function ($value, $key, $index) {
					return '<i class="fa fa-square text-'.$value->getColor().'"></i>&nbsp;&nbsp;'.$value->getName();
				},
				'choice_value' => function ($value) {
					if($value != null){
						return $value->getId();				
					}
					return $value;
				},
		])
		->add('contentType', EntityType::class, [
				'class' => 'AppBundle:ContentType',
				'query_builder' => function (EntityRepository $er) {
					return $er->createQueryBuilder('ct')
					->where("ct.deleted = false")
					->orderBy('ct.orderKey');
					
				},
				'choice_label' => function ($value, $key, $index) {
					return '<i class="'.$value->getIcon().' text-'.$value->getColor().'"></i>&nbsp;&nbsp;'.$value->getSingularName();
				},
				'multiple' => true,
				'required' => false,
				'choice_value' => function ($value) {
					if($value != null){
						return $value->getId();
					}
					return $value;
				},
				'attr' => [
						'class' => 'select2'
				],
		])

		->add('filter', SubmitEmsType::class, [
				'attr' => [ 
						'class' => 'btn-primary btn-md' 
				],
				'icon' => 'fa fa-columns'
		]);
	}
	
}