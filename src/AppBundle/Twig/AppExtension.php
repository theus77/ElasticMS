<?php
namespace AppBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry;
use AppBundle\Form\DataField\DateFieldType;
use AppBundle\Form\DataField\TimeFieldType;

class AppExtension extends \Twig_Extension
{
	protected $doctrine;
	
	public function __construct(Registry $doctrine)
	{
		$this->doctrine = $doctrine;
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
		);
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
			//throw error "argument 'function' is obligator"
		}
		
		$response = null;
		if ($arguments && array_key_exists('parameters', $arguments)){
			$response = $soapClient->$function($arguments['parameters']);
		}else{
			$response = $soapClient->$function();
		}
		
		if ($arguments && array_key_exists('result', $arguments)){
			return $response->$arguments['result'];
		} else {
			return $response;
		}
		
	}

	public function getName()
	{
		return 'app_extension';
	}
}