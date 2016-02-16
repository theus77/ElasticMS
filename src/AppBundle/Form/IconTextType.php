<?php
namespace AppBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class IconTextType extends AbstractType
{
	
	/**
	 * {@inheritdoc}
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
				'compound' => false,
				'icon' => 'fa fa-key',
		));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		$view->vars['icon'] = $options['icon'];
	}
	
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
    	return TextType::class;
    }
    

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
    	return 'icontext';
    }
}
