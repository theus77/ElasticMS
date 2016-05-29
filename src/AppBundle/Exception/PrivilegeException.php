<?php

namespace AppBundle\Exception;


use AppBundle\Entity\Revision;

class PrivilegeException extends \Exception
{
	
	private $revision;
	
	public function __construct(Revision $revision) {
		$this->revision = $revision;
		$message = "Not enough privilege the manipulate the object ".$revision->getContentType()->getName().":".$revision->getOuuid();
		parent::__construct($message, 0, null);
	}

	public function getRevision() {
		return $this->revision;
	}
	
}
