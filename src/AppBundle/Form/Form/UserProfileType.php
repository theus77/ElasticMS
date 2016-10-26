<?php

namespace AppBundle\Form\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Service\UserService;

class UserProfileType extends AbstractType {
	
	/**@var UserService */
	private $userService;
	
	public function __construct(UserService $userService) {
		$this->userService = $userService;
	}
	
	
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {

		$builder->add ('displayName');
		if($options['withWysiwygOptions']){
			$builder->add('wysiwygProfile', ChoiceType::class, [
						'required' => true,
						'label' => 'WYSIWYG profile',
						'choices' => [
							'Standard' => 'standard',
							'Light' => 'light',
							'Full' => 'full',
							'Custom' => 'custom'
						]
				])
				->add('wysiwygOptions', TextareaType::class, [
						'required' => false,
						'label' => 'WYSIWYG custom options',
						'attr' => [
							'rows' => 8,
						]
				]);			
		}
		
		$builder->remove('username');
	}
	

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults ( [
				'withWysiwygOptions' => $this->userService->getCurrentUser()->getAllowedToConfigureWysiwyg(),
		]);
	}
	
	public function getParent()
	{
		return 'FOS\UserBundle\Form\Type\ProfileFormType';
	}
	
}
