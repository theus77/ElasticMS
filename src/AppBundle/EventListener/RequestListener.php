<?php 
namespace AppBundle\EventListener;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
	protected $twig;
	protected $doctrine;
	
	public function __construct(\Twig_Environment $twig, Registry $doctrine )
	{
		$this->twig = $twig;
		$this->doctrine = $doctrine;
	}
	
    public function onKernelRequest(GetResponseEvent $event)
    {
    	$repository = $this->doctrine->getRepository('AppBundle:ContentType');
    	$contentTypes = $repository->findAll();
    	
    	
        $this->twig->addGlobal('contentTypes', $contentTypes);
    }
	
}


