<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle;
use AppBundle\Entity\Job;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

class JobController extends AppController
{

	/**
	 * @Route("/job/status/{job}", name="job.status"))
	 */
	public function jobStatusAction(Job $job, Request $request)
	{
		
		$converter = new AnsiToHtmlConverter();
		
		return $this->render( 'job/status.html.twig', [
				'job' =>  $job,
				'output' => $converter->convert($job->getOutput()),
		] );
	}
}