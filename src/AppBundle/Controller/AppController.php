<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Job;
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
	

	protected function startJob($service, $arguments){
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		$job = new Job();
		$job->setUser($this->getUser()->getUsername());
		$job->setDone(false);
		$job->setArguments($arguments);
		$job->setProgress(0);
		$job->setService($service);
		$job->setStatus("Job intialized");
		$em->persist($job);
		$em->flush();
		
		return $this->redirectToRoute('job.status', [
			'job' => $job->getId(),
		]);
	}
	

	protected function startConsole(Job $job){
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		$job->setUser($this->getUser()->getUsername());
		$job->setDone(false);
		$job->setProgress(0);
		$job->setStatus("Job intialized");
		
		$em->persist($job);
		$em->flush();
		
		return $this->redirectToRoute('job.status', [
			'job' => $job->getId(),
		]);
	}

	public static function getFormatedTimestamp(){
		return date('_Ymd_His');
	}
	
	protected function getGUID(){
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
