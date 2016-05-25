<?php

namespace AppBundle\Form\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

class RolePickerType extends SelectPickerType {
	
	//TODO: it would be nice to generate this list form the actual list
	private $choices = [
		'not-defined' => null,
		'ROLE_USER' => 'ROLE_USER',      
        'ROLE_SUPER_USER' => 'ROLE_SUPER_USER',      
        'ROLE_AUTHOR' => 'ROLE_AUTHOR',      
        'ROLE_SUPER_AUTHOR' => 'ROLE_SUPER_AUTHOR',      
        'ROLE_PUBLISHER' => 'ROLE_PUBLISHER',      
        'ROLE_SUPER_PUBLISHER' => 'ROLE_SUPER_PUBLISHER',      
        'ROLE_WEBMASTER' => 'ROLE_WEBMASTER',      
        'ROLE_SUPER_WEBMASTER' => 'ROLE_SUPER_WEBMASTER',      
        'ROLE_ADMIN' => 'ROLE_ADMIN',      
        'ROLE_SUPER_ADMIN' => 'ROLE_SUPER_ADMIN',   
	];
	
	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'choices' => $this->choices,
			'attr' => [
					'data-live-search' => true
			],
			'choice_attr' => function($category, $key, $index) {
				//TODO: it would be nice to translate the roles
				return [
						'data-content' => "<div class='text-".$category."'><i class='fa fa-square'></i>&nbsp;&nbsp;".$this->humanize($key).'</div>'
				];
			},
			'choice_value' => function ($value) {
		       return $value;
		    },
		));
	}
}
