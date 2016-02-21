<?php

namespace AppBundle\Form;

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
    	    	
    	
        $builder
	        ->add($revision->getContentType()->getFieldType()->getName(), $revision->getContentType()->getFieldType()->getType(), [
	        		'metadata' => $revision->getContentType()->getFieldType(),
	        ])
			->add('save', SubmitType::class, [
				'attr' => ['class' => 'btn-primary btn-sm '],
			])
			->add('publish', SubmitType::class, [
				'attr' => ['class' => 'btn-primary btn-sm '],
			])
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
