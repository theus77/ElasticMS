<?php
namespace AppBundle\Twig;

use AppBundle\Form\DataField\DateFieldType;
use AppBundle\Form\DataField\TimeFieldType;
use AppBundle\Service\UserService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use AppBundle\Service\ContentTypeService;
use Elasticsearch\Client;
use Symfony\Component\Routing\Router;

class AppExtension extends \Twig_Extension
{
	private $doctrine;
	private $userService;
	private $authorizationChecker;
	/**@var ContentTypeService $contentTypeService*/
	private $contentTypeService;
	/**@var Client $client */
	private $client;
	/**@var Router $router*/
	private $router;
	
	public function __construct(Registry $doctrine, AuthorizationCheckerInterface $authorizationChecker, UserService $userService, ContentTypeService $contentTypeService, Client $client, Router $router)
	{
		$this->doctrine = $doctrine;
		$this->authorizationChecker = $authorizationChecker;
		$this->userService = $userService;
		$this->contentTypeService = $contentTypeService;
		$this->client = $client;
		$this->router = $router;
	}
	
	public function getFilters()
	{
		
		
		return array(
				new \Twig_SimpleFilter('searches', array($this, 'searchesList')),
				new \Twig_SimpleFilter('dump', array($this, 'dump')),
				new \Twig_SimpleFilter('inArray', array($this, 'inArray')),
				new \Twig_SimpleFilter('firstInArray', array($this, 'firstInArray')),
				new \Twig_SimpleFilter('md5', array($this, 'md5')),
				new \Twig_SimpleFilter('convertJavaDateFormat', array($this, 'convertJavaDateFormat')),
				new \Twig_SimpleFilter('getTimeFieldTimeFormat', array($this, 'getTimeFieldTimeFormat')),
				new \Twig_SimpleFilter('soapRequest', array($this, 'soapRequest')),
				new \Twig_SimpleFilter('luma', array($this, 'relativeluminance')),
				new \Twig_SimpleFilter('contrastratio', array($this, 'contrastratio')),
				new \Twig_SimpleFilter('all_granted', array($this, 'all_granted')),
				new \Twig_SimpleFilter('one_granted', array($this, 'one_granted')),
				new \Twig_SimpleFilter('in_my_circles', array($this, 'inMyCircles')),
				new \Twig_SimpleFilter('data_link', array($this, 'dataLink')),
		);
	}
	
	private function superizer($role){
		if(strpos($role, '_SUPER_')){
			return $role;
		}
		return str_replace('ROLE_', 'ROLE_SUPER_', $role);
	}
	
	function all_granted($roles, $super=false){
		foreach ($roles as $role){
			if(!$this->authorizationChecker->isGranted($super?$this->superizer($role):$role)){
				return false;
			}
		}
		return true;
	}
	
	function inMyCircles($circles){
		
		if(!$circles){
			return true;
		}
		else if ($this->authorizationChecker->isGranted('ROLE_ADMIN')){
			return true;
		}
		else if (is_array($circles)){
			if(count($circles) > 0){
				$user = $this->userService->getCurrentUser();
				return count(array_intersect($circles, $user->getCircles())) > 0;
			}
			else {
				return true;
			}
		}
		else if(is_string($circles)){
			return in_array($circles, $user->getCircles());
		}
		
		
		return false;
	}
	
	
	function dataLink($key){
		$out = $key;
		$splitted = explode(':', $key);
		if($splitted && count($splitted) == 2){
			$type = $splitted[0];
			$ouuid =  $splitted[1];
			
			$addAttribute = "";
			
			/**@var \AppBundle\Entity\ContentType $contentType*/
			$contentType = $this->contentTypeService->getByName($type);
			if($contentType) {
				if($contentType->getIcon()){
					
					$icon = '<i class="'.$contentType->getIcon().'"></i>&nbsp;';
				}
				else{
					$icon = '<i class="fa fa-book"></i>&nbsp;';
				}
				
				$result = $this->client->get([
						'id' => $ouuid,
						'index' => $contentType->getEnvironment()->getAlias(),
						'type' => $type,
				]);
				
				if($contentType->getLabelField()){
					$label = $result['_source'][$contentType->getLabelField()];
					if($label && strlen($label) > 0){
						$out = $label;
					}
				}
				$out = $icon.$out;
				
				if($contentType->getColorField() && $result['_source'][$contentType->getColorField()]){
					$color = $result['_source'][$contentType->getColorField()];
					$contrasted = $this->contrastratio($color, '#000000') > $this->contrastratio($color, '#ffffff')?'#000000':'#ffffff';
					
					$out = '<span class="" style="color:'.$contrasted.';">'.$out.'</span>';
					$addAttribute = ' style="background-color: '.$result['_source'][$contentType->getColorField()].';border-color: '.$result['_source'][$contentType->getColorField()].';"';
					
				}
				
			}
			$out = '<a class="btn btn-primary btn-sm" href="'.$this->router->generate('data.revisions', [
					'type' =>$type,
					'ouuid' => $ouuid,
			]).'" '.$addAttribute.' >'.$out.'</a>';
		}
		return $out;
	}
	
	function one_granted($roles, $super=false){
		foreach ($roles as $role){
			if($this->authorizationChecker->isGranted($super?$this->superizer($role):$role)){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Calculate relative luminance in sRGB colour space for use in WCAG 2.0 compliance
	 * @link http://www.w3.org/TR/WCAG20/#relativeluminancedef
	 * @param string $col A 3 or 6-digit hex colour string
	 * @return float
	 * @author Marcus Bointon <marcus@synchromedia.co.uk>
	 */
	function relativeluminance($col) {
		//Remove any leading #
		$col = trim($col, '#');
		//Convert 3-digit to 6-digit
		if (strlen($col) == 3) {
			$col = $col[0] . $col[0] . $col[1] . $col[1] . $col[2] . $col[2];
		}
		//Convert hex to 0-1 scale
		$components = array(
				'r' => hexdec(substr($col, 0, 2)) / 255,
				'g' => hexdec(substr($col, 2, 2)) / 255,
				'b' => hexdec(substr($col, 4, 2)) / 255
		);
		//Correct for sRGB
		foreach($components as $c => $v) {
			if ($v <= 0.03928) {
				$components[$c] = $v / 12.92;
			} else {
				$components[$c] = pow((($v + 0.055) / 1.055), 2.4);
			}
		}
		//Calculate relative luminance using ITU-R BT. 709 coefficients
		return ($components['r'] * 0.2126) + ($components['g'] * 0.7152) + ($components['b'] * 0.0722);
	}
	
	/**
	 * Calculate contrast ratio acording to WCAG 2.0 formula
	 * Will return a value between 1 (no contrast) and 21 (max contrast)
	 * @link http://www.w3.org/TR/WCAG20/#contrast-ratiodef
	 * @param string $c1 A 3 or 6-digit hex colour string
	 * @param string $c2 A 3 or 6-digit hex colour string
	 * @return float
	 * @author Marcus Bointon <marcus@synchromedia.co.uk>
	 */
	function contrastratio($c1, $c2) {
		$y1 = $this->relativeluminance($c1);
		$y2 = $this->relativeluminance($c2);
		//Arrange so $y1 is lightest
		if ($y1 < $y2) {
			$y3 = $y1;
			$y1 = $y2;
			$y2 = $y3;
		}
		return ($y1 + 0.05) / ($y2 + 0.05);
	}
	
	public function md5($value)
	{
    	return md5($value);
	}

	public function searchesList($username)
	{
		$searchRepository = $this->doctrine->getRepository('AppBundle:Form\Search');
    	$searches = $searchRepository->findBy([
    		'user' => $username
    	]);
    	return $searches;
	}

	public function dump($object)
	{
    	dump($object);
	}

	public function convertJavaDateFormat($format)
	{
    	return DateFieldType::convertJavaDateFormat($format);
	}

	public function getTimeFieldTimeFormat($options)
	{
    	return TimeFieldType::getFormat($options);
	}

	public function inArray($needle, $haystack)
	{
		return is_int(array_search($needle, $haystack));
	}

	public function firstInArray($needle, $haystack)
	{
		return array_search($needle, $haystack) === 0;
	}
	
	/*
	 * $arguments should contain 'function' key. Optionally 'options' and/or 'parameters'
	 */
	public function soapRequest($wsdl, $arguments = null)
	{
		/** @var \SoapClient $soapClient */
		$soapClient = null;
		if ($arguments && array_key_exists('options', $arguments)){
			$soapClient = new \SoapClient($wsdl, $arguments['options']);
		} else {
			$soapClient = new \SoapClient($wsdl);
		}
		
		$function = null;
		if ($arguments && array_key_exists('function', $arguments)){
			$function = $arguments['function'];
		} else {
			//TODO: throw error "argument 'function' is obligator"
		}
		
		$response = null;
		if ($arguments && array_key_exists('parameters', $arguments)){
			$response = $soapClient->$function($arguments['parameters']);
		}else{
			$response = $soapClient->$function();
		}
		
		return $response;
		
	}

	public function getName()
	{
		return 'app_extension';
	}
}