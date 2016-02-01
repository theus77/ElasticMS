<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IndexController extends Controller
{
	/**
	 * @Route("/index/create")
	 */
	public function createAction()
	{
		return $this->render( 'index/create.html.twig' );
	}
}