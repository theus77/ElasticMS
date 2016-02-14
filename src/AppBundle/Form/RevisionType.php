<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\Revision;
use AppBundle\Entity\FieldType;

class RevisionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	
    	/** @var Revision $revision */
    	$revision = $builder->getData();
    	
    	/** @var FieldType $fieldType */
    	foreach ( $revision->getContentType()->getFieldTypes() as $key =>  $fieldType ){
    		
//     		$tempForm = $builder->create('temp', TextType::class);

    		
    		switch ($fieldType->getType()){
    			case 'ouuid':
		    		$builder->add($fieldType->getName(), OuuidType::class);
    				break;
    			case 'string':
		    		$builder->add($fieldType->getName(), StringType::class);
		    		break;
    			default:
    		}
    		
    	}
    	
    	
//     	$builder->
    	
        $builder
//             ->add('ouuid')
// 			->add('dataFields', CollectionType::class, [
// 					'entry_type'   => DataFieldType::class,
// 					'entry_options' => [
// 							'coucou' => 'foo'
// 					],
// 				])
			->add('discard', SubmitType::class)
			->add('save', SubmitType::class)
			->add('publish', SubmitType::class)
        ;
			
// 			dump($builder->get('dataFields')->);
        
			
			
		// In order to load the correct associated entity's formType,
		// I need to get the form data. But it doesn't exist yet.
		// So I need to use an Event Listener
		/*$builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
			// Get the current form
			$form = $event->getForm();
			// Get the data for this form (in this case it's the sub form's entity)
			// not the main form's entity
			$viewVersion = $event->getData();
			// Since the variables I need are in the parent entity, I have to fetch that
			$view = $viewVersion->getView();
			// Add the associated sub formType for the Content Type specified by this view
			// create a dynamic path to the formType
			$contentPath = $view->namespace_bundle.'\\Form\\Type\\'.$view->getContentType()->getBundle().'Type';
			// Add this as a sub form type
			$form->add('content', new $contentPath, array(
					'label' => false
			));
		});/**/
            
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Revision'
        ));
    }
}
