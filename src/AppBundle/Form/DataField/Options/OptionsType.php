<?php 

namespace AppBundle\Form\DataField\Options;

use AppBundle\Entity\FieldType;

use AppBundle\Form\DataField\Options\DisplayOptionsType;
use AppBundle\Form\DataField\Options\MappingOptionsType;
use AppBundle\Form\DataField\Options\RestrictionOptionsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


/**
 * It's the default option compound field of eMS data type.
 * The panes for display and mapping options are added.
 *
 * @author Mathieu De Keyzer <ems@theus.be>
 */
class OptionsType extends AbstractType
{
	
	/**
	 * {@inheritdoc}
	 */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	$builder->add ( 'displayOptions',     DisplayOptionsType::class);
	    $builder->add ( 'mappingOptions',     MappingOptionsType::class); 
	    $builder->add ( 'restrictionOptions', RestrictionOptionsType::class); 
	    $builder->add ( 'migrationOptions',	  MigrationOptionsType::class); 
	    $builder->add ( 'otherOptions',	      OtherOptionsType::class); 
    }   
	
	/**
	 * {@inheritdoc}
	 */
	public function getBlockPrefix() {
		return 'data_field_options';
	}
	
	public function hasMappingOptions() {
		return false;
	}
	
	public function hasMigrationOptions() {
		return true;
	}
	
	public function hasOtherOptions() {
		return true;
	}
	
	public static function generateMapping(array $options, FieldType $current){
		return [];
	}
}