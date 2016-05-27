<?php
namespace AppBundle\Controller;

use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
	/**
	 * @Route("/user", name="user.index"))
	 */
	public function indexAction(Request $request)
	{

		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();
		
		/** @var EntityRepository $repository */
		$repository = $em->getRepository('AppBundle:User');
		
		$users = $repository->findAll();
	
		return $this->render( 'user/index.html.twig', [
				'users' => $users
		] );
	}
}