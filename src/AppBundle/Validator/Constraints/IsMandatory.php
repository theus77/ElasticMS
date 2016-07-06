<?php
namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsMandatory extends Constraint
{
	public $message = 'This field is mandatory';
}