<?php

namespace AppBundle\Service;

use AppBundle\Entity\I18n;
use Doctrine\Bundle\DoctrineBundle\Registry;
use AppBundle\Repository\I18nRepository;
use Doctrine\ORM\EntityManager;


class I18nService {
	
	/**@var Registry $doctrine */
	private $doctrine;
	/** @var I18nRepository $repository */
	private $repository;
	/** @var EntityManager $manager */
	private $manager;
	
	public function __construct(Registry $doctrine)
	{
		$this->doctrine = $doctrine;
		$this->manager = $this->doctrine->getManager();
		$this->repository = $this->manager->getRepository('AppBundle:I18n');
	}

	public function count() {
		return $this->repository->count();
	}

	public function delete(I18n $i18n) {
		$this->manager->remove($i18n);
		$this->manager->flush();
	}
	
	/**
	 * Call to generate list of i18n keys
	 * 
	 * @return array Notification
	 */
	public function findAll($from, $limit, $filters = null) {
		return $this->repository->findBy([], ['identifier'=> 'asc'], $limit, $from);
	}
	
}