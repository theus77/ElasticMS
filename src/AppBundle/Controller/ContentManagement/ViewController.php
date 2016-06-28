<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle;
use AppBundle\Entity\View;
use AppBundle\Form\Field\IconPickerType;
use AppBundle\Form\Field\IconTextType;
use AppBundle\Form\Field\SubmitEmsType;
use AppBundle\Form\Form\ViewType;
use AppBundle\Repository\ContentTypeRepository;
use AppBundle\Repository\ViewRepository;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ViewController extends AppController
{
	/**
	 * @Route("/view/{type}", name="view.index")
	 */
	public function indexAction($type, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
		
		$contentTypes = $contentTypeRepository->findBy([
			'deleted' => false,
			'name' => $type,
		]);
			
		if(!$contentTypes || count($contentTypes) != 1) {
			throw new NotFoundHttpException('Content type not found');
		}
		
		
		return $this->render( 'view/index.html.twig', [
				'contentType' => $contentTypes[0]
		]);
		
		
	}
	
	/**
	 * @Route("/view/add/{type}", name="view.add")
	 */
	public function addAction($type, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var ContentTypeRepository $contentTypeRepository */
		$contentTypeRepository = $em->getRepository('AppBundle:ContentType');
		
		$contentTypes = $contentTypeRepository->findBy([
			'deleted' => false,
			'name' => $type,
		]);
			
		if(!$contentTypes || count($contentTypes) != 1) {
			throw new NotFoundHttpException('Content type not found');
		}
		
		$view = new View();
		$view->setContentType($contentTypes[0]);
		
		$form = $this->createForm ( ViewType::class, $view );
		
		$form->handleRequest ( $request );
		
		if ($form->isSubmitted () && $form->isValid ()) {
			$em->persist($view);
			$em->flush();
			
			$this->addFlash('notice', 'A new view has been created');
			
			return $this->redirectToRoute('view.edit', [
					'id' => $view->getId()
			]);
		}
		
		return $this->render( 'view/add.html.twig', [
				'contentType' => $contentTypes[0],
				'form' => $form->createView()
		]);
		
		
	}
	
	/**
	 * @Route("/view/edit/{id}", name="view.edit")
	 */
	public function editAction($id, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var ViewRepository $viewRepository */
		$viewRepository = $em->getRepository('AppBundle:View');
		
		/** @var View $view **/
		$view = $viewRepository->find($id);
			
		if(!$view) {
			throw new NotFoundHttpException('View type not found');
		}
		
		$form = $this->createFormBuilder ( $view )
		->add ( 'name', IconTextType::class, [
				'icon' => 'fa fa-tag'
		] )
		->add ( 'icon', IconPickerType::class, [
				'required' => false,
		])
		->add ( 'options', get_class($this->get($view->getType())))
		->add ( 'save', SubmitEmsType::class, [
				'attr' => [
						'class' => 'btn-primary btn-sm '
				],
				'icon' => 'fa fa-save'
		] )->getForm ();
		
		$form->handleRequest ( $request );
		
		if ($form->isSubmitted () && $form->isValid ()) {
			$em->persist($view);
			$em->flush();

			$this->addFlash('notice', 'A view has been updated');
			
			return $this->redirectToRoute('view.index', [
					'type' => $view->getContentType()->getName()
			]);
		}
		
		return $this->render( 'view/edit.html.twig', [
				'form' => $form->createView(),
				'view' => $view
		]);
	}
	
	/**
	 * @Route("/view/remove/{id}", name="view.remove")
     * @Method({"POST"})
	 */
	public function removeAction($id, Request $request)
	{
		
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		/** @var ViewRepository $viewRepository */
		$viewRepository = $em->getRepository('AppBundle:View');
		
		/** @var View $view **/
		$view = $viewRepository->find($id);
			
		if(!$view) {
			throw new NotFoundHttpException('View not found');
		}
		
		$em->remove($view);
		$em->flush();

		$this->addFlash('notice', 'A view has been removed');
			
		return $this->redirectToRoute('view.index', [
				'type' => $view->getContentType()->getName()
		]);
	}
}