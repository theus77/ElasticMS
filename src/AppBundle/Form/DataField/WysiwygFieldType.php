<?php

namespace AppBundle\Form\DataField;


use AppBundle\Form\Field\AnalyzerPickerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as TextareaSymfonyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WysiwygFieldType extends DataFieldType {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'WYSIWYG field';
	}
	
	/**
	 *
	 * @param FormBuilderInterface $builder        	
	 * @param array $options        	
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add ( 'text_value', TextareaSymfonyType::class, [ 
				'attr' => [ 
						'class' => 'ckeditor' 
				],
				'label' => $options['label'],
				'required' => false,
		] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		/*get options for twig context*/
		parent::buildView($view, $form, $options);
		$view->vars ['icon'] = $options ['icon'];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		/*set the default option value for this kind of compound field*/
		parent::configureOptions($resolver);
		$resolver->setDefault('icon', null);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'structuredOptions' );
		
		// String specific mapping options
		$optionsForm->get ( 'mappingOptions' )->add ( 'analyzer', AnalyzerPickerType::class);
	}
}