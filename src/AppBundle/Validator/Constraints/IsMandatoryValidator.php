<?php
namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use AppBundle\Entity\DataField;

class IsMandatoryValidator extends ConstraintValidator
{
	public function validate(DataField $dataField, Constraint $constraint)
	{
		if($dataField instanceof DataField) {
			//dump($dataField);
			//Get FieldType mandatory option
			$restrictionOptions = $dataField->getFieldType()->getRestrictionOptions();
			if(isset($restrictionOptions["mandatory"]) && true == $restrictionOptions["mandatory"]) {
				$rawData = $dataField->getRawData();
				if(!isset($rawData) || empty($rawData) || $rawData === null) {
					$this->context->buildViolation($constraint->message)
					->addViolation();
				}
			}
		} else {
			throw new UnexpectedTypeException($dataField, 'DataFiled');
//			throw new \Exception("IsMandatoryValidator validate only DataFields. ".get_class($dataField)." provided.");
		}
	}
}