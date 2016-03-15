<?php

namespace AppBundle\Form\Form;

use AppBundle\Entity\Revision;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\Field\SubmitEmsType;

class RevisionType extends AbstractType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		
		/** @var Revision $revision */
		$revision = $builder->getData ();
		
		$builder->add ( 'dataField', $revision->getContentType ()->getFieldType ()->getType (), [ 
				'metadata' => $revision->getContentType ()->getFieldType () 
		] )->add ( 'save', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'fa fa-save' 
		] );
		if($revision->getDraft()){
			$builder->add ( 'publish', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'glyphicon glyphicon-open' ,
				'label' => 'Finalize draft'
			] );
		}
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults ( array (
				'compound' => true,
				'data_class' => 'AppBundle\Entity\Revision' 
		) );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getBlockPrefix() {
		return 'revision';
	}
}
