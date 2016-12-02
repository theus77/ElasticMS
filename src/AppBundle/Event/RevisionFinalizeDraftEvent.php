<?php
namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use AppBundle\Entity\Revision;

/**
 */
class RevisionFinalizeDraftEvent extends Event
{
	const NAME = 'revision.finalize_draft';

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