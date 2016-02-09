# ************************************************************
# Sequel Pro SQL dump
# Version 4499
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Hôte: 127.0.0.1 (MySQL 5.6.22-log)
# Base de données: elasticms
# Temps de génération: 2016-02-09 22:26:48 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Affichage de la table content_type
# ------------------------------------------------------------

DROP TABLE IF EXISTS `content_type`;

CREATE TABLE `content_type` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `icon` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `lockBy` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lockUntil` datetime DEFAULT NULL,
  `circles` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:simple_array)',
  `defaultEnvironmentId` bigint(20) NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `color` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `labelField` bigint(20) NOT NULL,
  `parentField` bigint(20) NOT NULL,
  `dateField` bigint(20) NOT NULL,
  `endDateField` bigint(20) NOT NULL,
  `locationField` bigint(20) NOT NULL,
  `ouuidField` bigint(20) NOT NULL,
  `imageField` bigint(20) NOT NULL,
  `videoField` bigint(20) NOT NULL,
  `orderKey` int(11) NOT NULL,
  `rootContentType` tinyint(1) NOT NULL,
  `pluralName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `startDateField` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `content_type` WRITE;
/*!40000 ALTER TABLE `content_type` DISABLE KEYS */;

INSERT INTO `content_type` (`id`, `created`, `modified`, `name`, `icon`, `description`, `lockBy`, `lockUntil`, `circles`, `defaultEnvironmentId`, `deleted`, `color`, `labelField`, `parentField`, `dateField`, `endDateField`, `locationField`, `ouuidField`, `imageField`, `videoField`, `orderKey`, `rootContentType`, `pluralName`, `startDateField`)
VALUES
	(1,'2016-02-02 00:00:00','2016-02-02 00:00:00','WYSIWYG','fa fa-html5',NULL,NULL,NULL,NULL,1,0,'orange',0,0,0,0,0,0,0,0,3,1,'WYSIWYGs',0),
	(2,'2016-02-02 00:00:00','2016-02-02 00:00:00','Label','fa fa-language',NULL,NULL,NULL,NULL,1,0,'teal',0,0,0,0,0,0,0,0,2,1,'Labels',0),
	(3,'2016-02-02 00:00:00','2016-02-02 00:00:00','Flight','fa fa-plane',NULL,NULL,NULL,NULL,1,0,'green',0,0,1,1,1,0,1,0,1,1,'Flights',0),
	(4,'2016-02-02 00:00:00','2016-02-02 00:00:00','Sitemap','fa fa-sitemap',NULL,NULL,NULL,NULL,1,0,NULL,0,1,0,0,0,0,0,0,10,1,'Sitemaps',0);

/*!40000 ALTER TABLE `content_type` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
