<?php

namespace AppBundle\Service;

use AppBundle\Entity\I18n;
use Doctrine\Bundle\DoctrineBundle\Registry;
use AppBundle\Repository\I18nRepository;


class I18nService {
	
	/**@var Registry $doctrine */
	private $doctrine;
	
	public function __construct(Registry $doctrine)
	{
		$this->doctrine = $doctrine;
	}
	
	/**
	 * Call to generate list of i18n keys
	 * 
	 * @return array Notification
	 */
	public function findAllI18n($from, $limit, $filters = null) {
		
// 		$contentTypes = null;
// 		if($filters != null) {
// 			if (isset($filters['identifier'])) {
// 				$contentTypes = $filters['identifier'];
// 			}
// 		} 
		
		$em = $this->doctrine->getManager();
		/** @var I18nRepository $repository */
		$repository = $em->getRepository('AppBundle:I18n');
		$i18ns = $repository->findAllI18n($from, $limit);

		$i18nsKeys = array();
		/**@var Notification $notification*/
		foreach ($i18ns as $i18n) {
					
			$arrayKey = explode('.', $i18n->getIdentifier());
			$i18nsKeys[$i18n->getIdentifier()]['id_key'] = implode($arrayKey, '_');
			$i18nsKeys[$i18n->getIdentifier()]['i18n'] = $i18n;
		}
			
		return $i18nsKeys;
	}
	
}