<?php 
namespace AppBundle\EventListener;


use AppBundle\Command\AbstractEmsCommand;
use AppBundle\Command\JobOutput;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RequestListener
{
	protected $twig;
	protected $doctrine;
	protected $logger;
	protected $router;
	protected $container;
	protected $authorizationChecker;
	
	public function __construct(\Twig_Environment $twig, Registry $doctrine, Logger $logger, Router $router, Container $container, AuthorizationCheckerInterface $authorizationChecker)
	{
		$this->twig = $twig;
		$this->doctrine = $doctrine;
		$this->logger = $logger;
		$this->router = $router;
		$this->container = $container;
		$this->authorizationChecker = $authorizationChecker;
	}
	
	public function onKernelException(GetResponseForExceptionEvent $event)
	{
		//hide all errors to unauthenticated users        
		$exception = $event->getException();
		
		if (!($exception instanceof NotFoundHttpException) && !$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
			$response = new RedirectResponse($this->router->generate('user.login'));
			$event->setResponse($response);
		}
	}
	
    public function provideTemplateTwigObjects(FilterControllerEvent $event)
    {
    	//TODO: move to twig appextension?
    	$repository = $this->doctrine->getRepository('AppBundle:ContentType');
    	$contentTypes = $repository->findBy([
    			'deleted' => false,
    			'rootContentType' => true,
    	],[
    			'orderKey' => 'ASC'
    	]);
    	
    	/** @var \AppBundle\Repository\RevisionRepository $revisionRepository */
    	$revisionRepository = $this->doctrine->getRepository('AppBundle:Revision');
    	
    	$draftCounterGroupedByContentType = [];
    	$temp = $revisionRepository->draftCounterGroupedByContentType();
    	foreach ($temp as $item){
    		$draftCounterGroupedByContentType[$item["content_type_id"]] = $item["counter"];
    	}

    	$this->twig->addGlobal('contentTypes', $contentTypes);
        $this->twig->addGlobal('draftCounterGroupedByContentType', $draftCounterGroupedByContentType);
    }
    
    public function startJob($event)
    {
    	if( $event->getRequest()->isMethod('POST') && $event->getResponse() instanceof RedirectResponse ){
    		/** @var RedirectResponse $redirect */
    		$redirect = $event->getResponse();
    		$params = $this->router->match($redirect->getTargetUrl());
    		
    		if(isset($params['_route']) && $params['_route'] == "job.status" && isset($params['job'])){
    			$this->logger->info('Job '.$params['job'].' can be started');
    			
    			/** @var \AppBundle\Repository\JobRepository $jobRepository */
    			$jobRepository = $this->doctrine->getRepository('AppBundle:Job');
    			/** @var \AppBundle\Entity\Job $job */
    			$job = $jobRepository->find($params['job']);
    			if($job && !$job->getDone()){
    				/** @var AbstractEmsCommand $command */
    				
    				$command = $this->container->get($job->getService());
    				$input = new ArrayInput($job->getArguments());
    				$output = new JobOutput($this->doctrine, $job);
    				$output->writeln("Job ready to be launch");
    				$command->run($input, $output);
    				$output->writeln("Job done");
    		
    				$job->setDone(true);
    				$job->setProgress(100);
    				
    				$this->doctrine->getManager()->persist($job);
    				$this->doctrine->getManager()->flush($job);
    				$this->logger->info('Job '.$params['job'].' completed.');
    			}
    		}
    	}
    	
    }
	
}


