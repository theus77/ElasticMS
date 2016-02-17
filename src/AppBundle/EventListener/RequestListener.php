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
	
}


