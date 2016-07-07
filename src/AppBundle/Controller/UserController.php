<?php
namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\Field\ObjectPickerType;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Util\LegacyFormHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\Field\SubmitEmsType;

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
	
	/**
	 *
	 * @Route("/user/add", name="user.add")
	 */
	public function addUserAction(Request $request)
	{
		$user = new User();
		$form = $this->createFormBuilder($user)
		->add('username', null, array('label' => 'form.username', 'translation_domain' => 'FOSUserBundle'))
		->add('email', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\EmailType'), array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle'))
		->add('plainPassword', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\RepeatedType'), array(
				'type' => LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\PasswordType'),
				'options' => array('translation_domain' => 'FOSUserBundle'),
				'first_options' => array('label' => 'form.password'),
				'second_options' => array('label' => 'form.password_confirmation'),
				'invalid_message' => 'fos_user.password.mismatch',))
		->add('circles', ObjectPickerType::class, [
				'multiple' => TRUE,
				'type' => $this->container->getParameter('circles_object'),
				'dynamicLoading' => false
				
		])
		->add('expiresAt', DateType::class, array(
				'required' => FALSE,
   				'widget' => 'single_text',
				'format' => 'd/M/y',
 				'html5' => FALSE,
				'attr' => array(
						'class' => 'datepicker',
				),
		))
		->add('roles', ChoiceType::class, array('choices' => $this->getExistingRoles(),
	        'label' => 'Roles',
	        'expanded' => true,
	        'multiple' => true,
	        'mapped' => true,))
	    ->add ( 'create', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'fa fa-plus',
		] )
		->getForm();
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			/** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
			$userManager = $this->get('fos_user.user_manager');
			
			$continue = TRUE;
			$continue = $this->userExist($user, 'add', $form);

			if ($continue) {
				$user->setEnabled(TRUE);
				$userManager->updateUser($user);	
				$this->addFlash(
					'notice',
					'User created!'
					);
				return $this->redirectToRoute('user.index');
			}
		}
		
		return $this->render('user/add.html.twig', array(
				'form' => $form->createView()
		));
	}
	
	/**
	 * 
	 * @Route("/user/{id}/edit", name="user.edit")
	 */
	public function editUserAction($id, Request $request)
	{
	
		$userManager = $this->get('fos_user.user_manager');
		$user = $userManager->findUserBy(array('id'=> $id));
		// test if user exist before modified it
		if(!$user){
			throw $this->createNotFoundException('user not found');
		}
	
		$form = $this->createFormBuilder($user)
		->add('email', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\EmailType'), array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle'))
		->add('username', null, array(
				'label' => 'form.username', 
				'translation_domain' => 'FOSUserBundle',
				'disabled' => true
		))
		->add('circles', ObjectPickerType::class, [
				'multiple' => TRUE,
				'type' => $this->container->getParameter('circles_object'),
				'dynamicLoading' => true
				
		])
		->add('enabled')
		->add('locked')
		->add('expiresAt', DateType::class, array(
				'required' => FALSE,
   				'widget' => 'single_text',
				'format' => 'd/M/y',
 				'html5' => FALSE,
				'attr' => array('class' => 'datepicker',
 					'data-date-format' => 'dd/mm/yyyy',
					'data-today-highlight' => FALSE,
					'data-week-start' => 1,
					'data-days-of-week-highlighted' => true,
					'data-days-of-week-disabled' => false,
					'data-multidate' => FALSE,
					
				),
		))
		->add('roles', ChoiceType::class, array('choices' => $this->getExistingRoles(),
	        'label' => 'Roles',
	        'expanded' => true,
	        'multiple' => true,
	        'mapped' => true,))
	    ->add ( 'update', SubmitEmsType::class, [ 
				'attr' => [ 
						'class' => 'btn-primary btn-sm ' 
				],
				'icon' => 'fa fa-save',
		] )
		->getForm();
		
		$form->handleRequest($request);
	
		if ($form->isSubmitted() && $form->isValid()) {
			/** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
			$userManager = $this->get('fos_user.user_manager');
			$continue = TRUE;
			$continue = $this->userExist($user, 'edit', $form);
			
			if ($continue) {
				$userManager->updateUser($user, false);
				$this->getDoctrine()->getManager()->flush();
				$this->addFlash(
						'notice',
						'User was modified!'
						);
				return $this->redirectToRoute('user.index');
			}
		}
	
		return $this->render('user/edit.html.twig', array(
				'form' => $form->createView(),
				'user' => $user
		));
	}
	
	/**
	 *
	 * @Route("/user/{id}/delete", name="user.delete")
	 */
	public function removeUserAction($id, Request $request)
	{
	
		$userManager = $this->get('fos_user.user_manager');
		$user = $userManager->findUserBy(array('id'=> $id));
		// test if user exist before modified it
		if(!$user){
			throw $this->createNotFoundException('user not found');
		}
		
		$userManager->deleteUser($user);
		$this->getDoctrine()->getManager()->flush();
		$this->addFlash(
				'notice',
				'User was deleted!'
				);
		return $this->redirectToRoute('user.index');
	}
	
	/**
	 *
	 * @Route("/user/{id}/enabling", name="user.enabling")
	 */
	public function enablingUserAction($id, Request $request)
	{
	
		$userManager = $this->get('fos_user.user_manager');
		$user = $userManager->findUserBy(array('id'=> $id));
		// test if user exist before modified it
		if(!$user){
			throw $this->createNotFoundException('user not found');
		}
		
		$message = "User was ";
		if ($user->isEnabled()) {
			$user->setEnabled(FALSE);
			$message = $message . "disabled !";
		} else {
			$user->setEnabled(TRUE);
			$message = $message . "enabled !";
		}
		
		$userManager->updateUser($user);
		$this->getDoctrine()->getManager()->flush();
		$this->addFlash(
				'notice',
				$message
				);
		return $this->redirectToRoute('user.index');
	}
	
	/**
	 *
	 * @Route("/user/{id}/locking", name="user.locking")
	 */
	public function lockingUserAction($id, Request $request)
	{
	
		$userManager = $this->get('fos_user.user_manager');
		$user = $userManager->findUserBy(array('id'=> $id));
		// test if user exist before modified it
		if(!$user){
			throw $this->createNotFoundException('user not found');
		}
		$message = "User was ";
		if ($user-> isLocked()) {
			$user->setLocked(FALSE);
			$message = $message . "unlocked !";
		} else {
			$user->setLocked(TRUE);
			$message = $message . "locked !";
		}
		
		$userManager->updateUser($user);
		$this->getDoctrine()->getManager()->flush();
		$this->addFlash(
				'notice',
				$message
				);
		return $this->redirectToRoute('user.index');
	}
	
	/**
	 * Test if email or username exist return on add or edit Form
	 */
	private function userExist ($user, $action, $form) {
		/** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
		$userManager = $this->get('fos_user.user_manager');
		$exists = array('email' => $userManager->findUserByEmail($user->getEmail()), 'username' => $userManager->findUserByUsername($user->getUsername()));
		$messages = array('email' => 'User email already exist!', 'username' => 'Username already exist!');
		foreach ($exists as $key => $value) {
			if ($value instanceof User) {
				if ($action == 'add' or ($action == 'edit' and $value->getId() != $user->getId()))
				{
					$this->addFlash(
						'error',
						$messages[$key]
					);	
					return FALSE;
				}
			}
		}
		return TRUE;
	}
	
	private  function getExistingRoles()
	{
	    $roleHierarchy = $this->container->getParameter('security.role_hierarchy.roles');
	    $roles = array_keys($roleHierarchy);
	    
	    $theRoles['ROLE_USER'] = 'ROLE_USER';
	    
	    foreach ($roles as $role) {
	        $theRoles[$role] = $role;
	    }
	    return $theRoles;
	}
}