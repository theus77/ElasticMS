<?php
namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use AppBundle\Entity\Revision;
use AppBundle\Entity\Environment;

/**
 */
class RevisionPublishEvent extends Event
{
	const NAME = 'revision.publish';

	protected $revision;
	protected $environment;

	public function __construct(Revision $revision, Environment $environment)
	{
		$this->revision = $revision;
		$this->environment = $environment;
	}

	public function getRevision()
	{
		return $this->revision;
	}

	public function getEnvironment()
	{
		return $this->environment;
	}
}