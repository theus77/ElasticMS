<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\I18n;
use AppBundle\Form\I18nType;

/**
 * I18n controller.
 *
 * @Route("/i18n")
 */
class I18nController extends Controller
{
    /**
     * Lists all I18n entities.
     *
     * @Route("/", name="i18n_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $count = $em->getRepository('AppBundle:I18n')->findAll();
        // for pagination
        $paging_size = $this->getParameter('paging_size');
        $lastPage = ceil(count($count)/$paging_size);
        if(null != $request->query->get('page')){
        	$page = $request->query->get('page');
        }
        else{
        	$page = 1;
        }
        
        $i18ns = $this->get('ems.service.i18n')->findAllI18n(($page-1)*$paging_size, $paging_size);
        return $this->render('i18n/index.html.twig', array(
            'i18nkeys' => $i18ns,
        	'lastPage' => $lastPage,
        	'paginationPath' => 'i18n_index',
        	'page' => $page,
        ));
    }

    /**
     * Creates a new I18n entity.
     *
     * @Route("/new", name="i18n_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $i18n = new I18n();
        $content = $i18n->getContent();
        
        $i18n->setContent(array(array('locale' => "", 'text' => "")));
        
        $form = $this->createForm('AppBundle\Form\I18nType', $i18n);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($i18n);
            $em->flush();

            return $this->redirectToRoute('i18n_show', array('id' => $i18n->getId()));
        }

        return $this->render('i18n/new.html.twig', array(
            'i18n' => $i18n,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a I18n entity.
     *
     * @Route("/{id}", name="i18n_show")
     * @Method("GET")
     */
    public function showAction(I18n $i18n)
    {
        $deleteForm = $this->createDeleteForm($i18n);

        return $this->render('i18n/show.html.twig', array(
            'i18n' => $i18n,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing I18n entity.
     *
     * @Route("/{id}/edit", name="i18n_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, I18n $i18n)
    {
        $deleteForm = $this->createDeleteForm($i18n);
        $editForm = $this->createForm('AppBundle\Form\I18nType', $i18n);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($i18n);
            $em->flush();

            return $this->redirectToRoute('i18n_edit', array('id' => $i18n->getId()));
        }

        return $this->render('i18n/edit.html.twig', array(
            'i18n' => $i18n,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a I18n entity.
     *
     * @Route("/{id}", name="i18n_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, I18n $i18n)
    {
        $form = $this->createDeleteForm($i18n);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($i18n);
            $em->flush();
        }

        return $this->redirectToRoute('i18n_index');
    }

    /**
     * Creates a form to delete a I18n entity.
     *
     * @param I18n $i18n The I18n entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(I18n $i18n)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('i18n_delete', array('id' => $i18n->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
