<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ContentTypeController extends Controller
{
	/**
	 * @Route("/webmaster/contenttype/add", name="contenttype.add"))
	 */
	public function addAction()
	{ 
		return $this->render( 'contenttype/add.html.twig' );
	}
}
