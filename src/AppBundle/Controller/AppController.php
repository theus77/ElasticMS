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
	
	
	protected function getGUID(){
		if (function_exists('com_create_guid')){
			return com_create_guid();
		}else{
			mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
			$charid = strtolower(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = 
			 substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12);
			return $uuid;
		}
	}
	
	
}
