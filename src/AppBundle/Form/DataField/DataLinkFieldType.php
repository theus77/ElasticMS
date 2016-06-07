<?php

namespace AppBundle\Form\DataField;

use AppBundle\Entity\DataField;
use AppBundle\Entity\FieldType;
use AppBundle\Form\Field\ObjectPickerType;
use AppBundle\Form\Field\Select2Type;
use Elasticsearch\Client;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;
use AppBundle\Repository\ContentTypeRepository;
												
/**
 * Defined a Container content type.
 * It's used to logically groups subfields together. However a Container is invisible in Elastic search.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 *        
 */
 class DataLinkFieldType extends DataFieldType {

 	/**@var Client $client*/
 	private $client;
 	/**@var EntityManager $em*/
 	private $em;
 	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function getLabel(){
		return 'Link to data object(s)';
	}
	
	/**
	 * Get a icon to visually identify a FieldType
	 * 
	 * @return string
	 */
	public static function getIcon(){
		return 'fa fa-sitemap';
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public static function buildObjectArray(DataField $data, array &$out) {
		if (! $data->getFieldType ()->getDeleted ()) {
			if($data->getFieldType()->getDisplayOptions()['multiple']){
				$out [$data->getFieldType ()->getName ()] = $data->getArrayTextValue();
			}
			else{
				parent::buildObjectArray($data, $out);
			}
				
		}
	}
	
	public function setClient(Client $client){
		$this->client = $client;
	}
	
	public function setEntityManager($doctrine){
		$this->em = $doctrine->getManager();
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {

		/** @var FieldType $fieldType */
		$fieldType = $options ['metadata'];
		
		
		if($options['dynamicLoading']){
			$builder->add ( $options['multiple']?'array_text_value':'text_value', ObjectPickerType::class, [
					'label' => (null != $options ['label']?$options ['label']:$fieldType->getName()),
					'required' => false,
					'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole()),
					'multiple' => $options['multiple'],
					'type' => $options['type'],
					'environment' => $options['environment'],
			] );				
		}
		else {
			$params = ['size' => 500];
			if ($options['type']) {
				$params['type'] = $options['type'];
			}
			if ($options['environment']) {
				$params['index'] = $options['environment'];
			}
			$result = $this->client->search($params);

			/** @var ContentTypeRepository $repository */
			$repository = $this->em->getRepository('AppBundle:ContentType');
			
			$contentTypes = $repository->findAllAsAssociativeArray();
			
			$choices = [];
			foreach ($result['hits']['hits'] as $item){
				if(isset($contentTypes[ $item['_type']])){
					$contentType = $contentTypes[ $item['_type']];
					$key = $item['_type'].':'.$item['_id'] ;
					
					//$label = '<i class="'.$contentType->getIcon().'"></i> ';
					if(null !== $contentType->getLabelField() && isset($item['_source'][$contentType->getLabelField()])){
						$label = $item['_source'][$contentType->getLabelField()]." (".$key.")";
					}
					else{
						$label = $key;
					}
					
					$choices[ $label ] = $key;					
				}
			}
			
			$builder->add ( $options['multiple']?'array_text_value':'text_value', Select2Type::class, [
					'label' => (isset($options['label'])?$options['label']:$fieldType->getName()),
					'required' => false,
					'disabled'=> !$this->authorizationChecker->isGranted($fieldType->getMinimumRole()),
					'choices' => $choices,
					'empty_data'  => null,
					'multiple' => $options['multiple'],
					'expanded' => false,
			] );			
		}
		
		
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function configureOptions(OptionsResolver $resolver) {
		/* set the default option value for this kind of compound field */
		parent::configureOptions ( $resolver );
		$resolver->setDefault ( 'multiple', false );
		$resolver->setDefault ( 'type', null );
		$resolver->setDefault ( 'environment', null );
		$resolver->setDefault ( 'required', false );
		$resolver->setDefault ( 'dynamicLoading', true );
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function buildOptionsForm(FormBuilderInterface $builder, array $options) {
		parent::buildOptionsForm ( $builder, $options );
		$optionsForm = $builder->get ( 'options' );
		
		// String specific display options
		$optionsForm->get ( 'displayOptions' )->add ( 'multiple', CheckboxType::class, [ 
				'required' => false,
		] )->add ( 'required', CheckboxType::class, [ 
				'required' => false,
		] )->add ( 'dynamicLoading', CheckboxType::class, [ 
				'required' => false,
		] )->add ( 'environment', TextType::class, [ 
				'required' => false,
		] )->add ( 'type', TextType::class, [ 
				'required' => false,
		] );
		
	}
}