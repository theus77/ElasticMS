<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
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
        $builder
            ->add('created', 'datetime')
            ->add('modified', 'datetime')
            ->add('deleted')
            ->add('ouuid')
            ->add('startTime', 'datetime')
            ->add('endTime', 'datetime')
            ->add('draft')
            ->add('lockBy')
            ->add('lockUntil', 'datetime')
            ->add('contentType')
        ;
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
