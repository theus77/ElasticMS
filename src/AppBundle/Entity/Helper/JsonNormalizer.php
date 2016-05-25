<?php
namespace AppBundle\Entity\Helper;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use AppBundle\Entity\FieldType;
use AppBundle\Entity\ContentType;

//http://symfony.com/doc/current/components/serializer.html
//http://php-and-symfony.matthiasnoback.nl/2012/01/the-symfony2-serializer-component-create-a-normalizer-for-json-class-hinting/

class JsonNormalizer implements NormalizerInterface
{
	//TODO: Anotate the object to allow the method normalize to be able get methods of the object to be skipped.
	//If you want to parse a new object, provide here the getXXXX method of the object to be skipped of normalization
	//[<ObjectName>] => [<XXXX>,...]
	private $toSkip = ["ContentType" => ["id", "indexAnalysisConfiguration"],
					   "FieldType" => ["id", "contentType", "parent", "children"]
	];
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function normalize($object, $format = null, array $context = Array()) {
		$data = array();

		$reflectionClass = new \ReflectionClass($object);

		$data['__jsonclass__'] = array(
				get_class($object),
				array(), // constructor arguments
		);
		//Parsing all methods of the object
		foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
			if (strtolower(substr($reflectionMethod->getName(), 0, 3)) !== 'get') {
				continue;
			}

			if ($reflectionMethod->getNumberOfRequiredParameters() > 0) {
				continue;
			}

			$property = lcfirst(substr($reflectionMethod->getName(), 3));
			$value = $reflectionMethod->invoke($object);
			
 			if ($property == "deleted" && $value == true) {
 				break;
 			}
 			if($value != NULL) {
 				//If you want to parse a new object, provide here the way to normalize it.
 				if($object instanceof ContentType) {
	 				if(in_array($property, $this->toSkip["ContentType"])) {
	 					continue;
	 				}
	 				if($value instanceof FieldType) {
	 					$value = $this->normalize($value, $format, $context);
	 				}
	 			} elseif($object instanceof FieldType) {
	 				if(in_array($property, $this->toSkip["FieldType"])) {
	 					continue;
	 				}
					if($property == "validChildren") {
						foreach ($value as $index => $subElement) {//subElement is always FieldType
							if(!$subElement->getDeleted()) {
								$arrayValues[$index] = $this->normalize($subElement, $format, $context);//Recursive
							}
						}
						$value = $arrayValues;
					}
	 			} 
 			}
			$data[$property] = $value;
		}
		
		return $data;
	}
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	//TODO: Refactoring
	public function denormalize($data, $class, $format = null) {
		$class = $data['__jsonclass__'][0];
		$reflectionClass = new \ReflectionClass($class);
	
		$constructorArguments = $data['__jsonclass__'][1] ?: array();
	
		$object = $reflectionClass->newInstanceArgs($constructorArguments);
	
		unset($data['__jsonclass__']);
	
		foreach ($data as $property => $value) {
			$setter = 'set' . $property;
			if (method_exists($object, $setter)) {
				$object->$setter($value);
			}
		}
	
		return $object;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function supportsNormalization($data, $format = null) {
		return is_object($data) && 'json' === $format;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function supportsDenormalization($data, $type, $format = null) {
		return isset($data['__jsonclass__']) && 'json' === $format;
	}
}
