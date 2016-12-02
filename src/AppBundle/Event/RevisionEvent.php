<?php
namespace AppBundle\Event;

use AppBundle\Entity\Revision;
use Symfony\Component\EventDispatcher\Event;

/**
 */
class RevisionEvent extends Event
{
	protected $revision;

	public function __construct(Revision $revision)
	{
		$this->revision = $revision;
	}

	public function getRevision()
	{
		return $this->revision;
	}
}