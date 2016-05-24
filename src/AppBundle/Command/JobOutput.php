<?php
namespace AppBundle\Command;



use Symfony\Component\Console\Output\Output;
use Doctrine\Bundle\DoctrineBundle\Registry;
use AppBundle\Entity\Job;

class JobOutput extends Output {
	private $doctrine;
	private $job;
	
	
	public function __construct(Registry $doctrine, Job $job){
		$this->doctrine = $doctrine;
		$this->job = $job;
		parent::__construct();
	}
	
	
	public function doWrite($message, $newline){
		$this->job->setStatus($message);
		
		$this->job->setOutput($this->job->getOutput().$message.($newline ? PHP_EOL : ''));
		$this->doctrine->getEntityManager()->persist($this->job);
	}
}