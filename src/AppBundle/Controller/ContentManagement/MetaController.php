<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle\Entity\ContentType;
use AppBundle;
use AppBundle\Entity\Revision;
use AppBundle\Form\IconTextType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class MetaController extends AppController
{
	/**
	 * @Route("/meta/add", name="contenttype.add"))
	 */
	public function addAction(Request $request)
	{
		$contentType = new ContentType();
		
		$form = $this->createFormBuilder($contentType)
		->add('name', IconTextType::class, [
				'icon' => 'fa fa-gear',
		])		
		->add('alias', IconTextType::class, [
				'label' => 'Default environment',
				'icon' => 'fa fa-database',
		])		
		->add('save', SubmitType::class, [
				'label' => 'Create',
				'attr' => [
						'class' => 'btn btn-primary pull-right'
				]
		])
		->getForm();
		
		$form->handleRequest($request);
			
		
		
		if ($form->isSubmitted() && $form->isValid()) {
		
			/** @var ContentType $revision */
			$contentType = $form->getData();
			
		}
		
		return $this->render( 'meta/add-meta.html.twig', [
				'form' => $form->createView()
		]);		
	}
}