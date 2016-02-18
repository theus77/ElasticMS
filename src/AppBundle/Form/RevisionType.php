<?php

namespace AppBundle\Form;

use AppBundle\Entity\FieldType;
use AppBundle\Entity\Revision;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
    		
    		switch ($fieldType->getType()){
    			case 'ouuid':
		    		$builder->add($fieldType->getName(), OuuidType::class, [
						'metadata' => $fieldType,
					]);
    				break;
    			case 'string':
		    		$builder->add($fieldType->getName(), StringType::class, [
						'metadata' => $fieldType,
					]);
		    		break;
		    	case 'container':
		    		$builder->add($fieldType->getName(), ContainerType::class, [
		    			'label' => $fieldType->getLabel(),
		    			'metadata' => $fieldType
		    		]);
		    		break;
    			default:
    		}
    		
    	}
    	    	
    	
        $builder
			->add('discard', SubmitType::class, [
					'attr' => [
							'class' => 'btn-default'
					]
			])
			->add('save', SubmitType::class)
			->add('publish', SubmitType::class)
        ;

            
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
    		'compound' => true,
            'data_class' => 'AppBundle\Entity\Revision'
        ));
    }

    
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
    	return 'revision';
    }
}
