<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\ContentType;
use Doctrine\Common\DataFixtures\AbstractFixture;

class LoadContentTypeData extends AbstractFixture implements OrderedFixtureInterface
{
	//private $currentTime;
	
	public function load(ObjectManager $manager)
	{
		//set globals
		//$this->currentTime = time(); //date("Y-m-d H:i:s");
		
		
		//Define twig code outside the data array (for readability purposes)
		$labelTwig = '
			<ul>	
				<li>Key: {{ source.key }}</li>
				<li>English: <b>{{ source.value_en }}</b></li>
				<li>French: <b>{{ source.value_fr }}</b></li>
				<li>Nederlands: <b>{{ source.value_nl }}</b></li>
			</ul>';
		$richTextTwig = '';
		$demoTwig = '';
		$versionTwig = '
			<div class="row">
				<div class="col-sm-2">
					<img class="img-responsive" src="http://global.theus.be/img/fr/{{ object._id }}/thumb.jpg" alt="Photo">
				</div>
			<div class="col-sm-10">
				This series was taken <strong>{{ object._source.date|date("d M Y") }}</strong>
				{% if object._source.artist  is defined %}n		by <strong>{{ object._source.artist }}</strong>
				{% endif %}
				(see it on <a href="http://global.theus.be/fr/galleries/version/{{ object._id }}" target="_blank">GlobalView</a>)
					<ul>
						<li>Rating:
							{% for i in 0..object._source.rating %}
			    					<i class="fa fa-fw fa-star"></i>
							{% endfor %}
						</li>
						<li>Name: {{ object._source.name }}</li>
						<li>Pixel size: {{ object._source.pixel_size }}</li>
						<li>Project: {{ object._source.project_name }}</li>
						{% if object. _source.model  is defined %}
							<li>Model: {{ object._source.model }}</li>
						{% endif %}
						{% if object. _source.lens_model  is defined %}
							<li>Lens: {{ object._source.lens_model }}</li>
						{% endif %}
					</ul>
			
				</div>
			</div>';
		
		//define content type specific fields
		$data = array(
				//name, 		pluralName, 	icon, 			description, 		indexTwig, 		color, 	orderKey, 	rootContentType, 	active, environment,	labelField	$locationField
				['label', 		'labels', 		'fa fa-key', 	'Translation keys', $labelTwig,    'red', 	1, 			1, 					1, 		'preview', 		'value_en',	null],
				['rich-text', 	'rich-texts', 	'fa fa-html5', 	'WYSIWYG fields', 	$richTextTwig, 'purple',2, 			1, 					1, 		'preview', 		null,		null],
				['demo', 		'demo', 		'fa fa-binoculars','Demo', 			$demoTwig,	   'yellow',3, 			1, 					1, 		'preview', 		null,		null],
				['version', 	'version', 		'fa fa-photo', 	'Other src', 		$versionTwig,  'pink',	4, 			1, 					1, 		'aperture',		'name',		'location'],
		);
	
		foreach ($data as $contentData)
		{
			$contentType = $this->createContentType(...$contentData);
			$manager->persist($contentType);
			$manager->flush();
			
			$name = $contentData[0];
			$this->addReference($name, $contentType); //Enable a fieldType to reference this contentType	
		}
	}
	
	public function getOrder()
	{
		// the order in which fixtures will be loaded
		// the lower the number, the sooner that this fixture is loaded
		return 2;
	}
	
	
	private function createContentType($name, $pluralName, $icon, $description, $indexTwig, $color, $orderKey, $rootContentType, $active, $environment, $labelField, $locationField)
	{
		$contentType = new ContentType();
		$contentType->setName($name);
		$contentType->setPluralName($pluralName);
		$contentType->setIcon($icon);
		$contentType->setDescription($description);
		$contentType->setIndexTwig($indexTwig);
		$contentType->setColor($color);
		$contentType->setOrderKey($orderKey);
		$contentType->setRootContentType($rootContentType);
		$contentType->setActive($active);
		$contentType->setEnvironment($this->getReference($environment));
		$contentType->setLabelField($labelField);
		$contentType->setLocationField($locationField);
	
		return $contentType;
	}
}