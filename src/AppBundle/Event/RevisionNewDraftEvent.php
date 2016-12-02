<?php
namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use AppBundle\Entity\Revision;

/**
 */
class RevisionNewDraftEvent extends RevisionEvent
{
	const NAME = 'revision.new_draft';
}