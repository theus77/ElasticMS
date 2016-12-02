<?php
namespace AppBundle\Event;

use AppBundle\Entity\Revision;
use Symfony\Component\EventDispatcher\Event;

/**
 */
class RevisionUnpublishEvent extends RevisionPublishEvent
{
	const NAME = 'revision.unpublish';
}