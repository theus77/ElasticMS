<?php
namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use AppBundle\Entity\Revision;

/**
 */
class RevisionFinalizeDraftEvent extends RevisionEvent
{
	const NAME = 'revision.finalize_draft';
}