<?php
namespace AppBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class Select2Type extends ChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
    	return ChoiceType::class;
    }
    

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
    	return 'select2';
    }
}
