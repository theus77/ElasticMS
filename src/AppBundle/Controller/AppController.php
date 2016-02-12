<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AppController extends Controller
{
	/**
	 * @Route("/js/app.js", name="app.js"))
	 */
	public function javascriptAction()
	{
		return $this->render( 'app/app.js.twig' );
	}

	/**
	 * @return \Elasticsearch\ClientBuilder
	 */
	protected function getElasticsearch()
	{
		return $this->get('app.elasticsearch');
	}

	/**
	 * @return \Twig_Environment
	 */
	protected function getTwig()
	{
		return $this->container->get('twig');
	}
	
}
