# ************************************************************
# Sequel Pro SQL dump
# Version 4499
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Hôte: 127.0.0.1 (MySQL 5.6.22-log)
# Base de données: elasticms
# Temps de génération: 2016-02-18 22:23:55 +0000
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
  `deleted` tinyint(1) NOT NULL,
  `color` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `labelField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parentField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dateField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `endDateField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locationField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ouuidField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `imageField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `videoField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `orderKey` int(11) NOT NULL,
  `rootContentType` tinyint(1) NOT NULL,
  `pluralName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `startDateField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userField` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alias` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `indexTwig` longtext COLLATE utf8_unicode_ci,
  `active` tinyint(1) NOT NULL,
  `field_types_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_41BCBAEC588AB49A` (`field_types_id`),
  CONSTRAINT `FK_41BCBAEC588AB49A` FOREIGN KEY (`field_types_id`) REFERENCES `field_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `content_type` WRITE;
/*!40000 ALTER TABLE `content_type` DISABLE KEYS */;

INSERT INTO `content_type` (`id`, `created`, `modified`, `name`, `icon`, `description`, `lockBy`, `lockUntil`, `circles`, `deleted`, `color`, `labelField`, `parentField`, `dateField`, `endDateField`, `locationField`, `ouuidField`, `imageField`, `videoField`, `orderKey`, `rootContentType`, `pluralName`, `startDateField`, `userField`, `alias`, `indexTwig`, `active`, `field_types_id`)
VALUES
	(1,'2016-02-02 00:00:00','2016-02-02 00:00:00','WYSIWYG','fa fa-html5',NULL,NULL,NULL,NULL,0,'orange','','','','','','','','',40,1,'WYSIWYGs','0',NULL,'draft','\n<div class=\"col-md-4\">\n	{{ source.value_en|raw }}\n</div>\n<div class=\"col-md-4\">\n	{{ source.value_fr|raw }}\n</div>\n<div class=\"col-md-4\">\n	{{ source.value_nl|raw }}\n</div>',1,NULL),
	(2,'2016-02-02 00:00:00','2016-02-02 00:00:00','Label','fa fa-language',NULL,NULL,NULL,NULL,0,'teal','value_en','','','','','','','',30,1,'Labels','0',NULL,'draft','<ul>\n	<li>Key: {{ source.key }}</li>\n	<li>English: <b>{{ source.value_en }}</b></li>\n	<li>French: <b>{{ source.value_fr }}</b></li>\n	<li>Nederlands: <b>{{ source.value_nl }}</b></li>\n</ul>',1,NULL),
	(3,'2016-02-02 00:00:00','2016-02-02 00:00:00','Flight','fa fa-plane',NULL,NULL,NULL,NULL,0,'green','','','plannedDated','1','1','','1','',20,1,'Flights','0',NULL,'draft',NULL,1,NULL),
	(4,'2016-02-02 00:00:00','2016-02-02 00:00:00','Sitemap','fa fa-sitemap',NULL,NULL,NULL,NULL,0,NULL,'','parent','','','','','','',50,1,'Sitemaps','0',NULL,'draft',NULL,1,NULL),
	(5,'2016-01-01 00:00:00','2016-01-01 00:00:00','version','fa fa-photo ',NULL,NULL,NULL,NULL,0,'red','label','','date','','location','uuid','uuid','',10,1,'versions','0',NULL,'aperture','\n<div class=\"row\">\n\n	<div class=\"col-sm-2\">\n		<img class=\"img-responsive\" src=\"http://global.theus.be/img/fr/{{ object._id }}/thumb.jpg\" alt=\"Photo\">\n	</div>\n\n	<div class=\"col-sm-10\">\n\n	This series was taken <strong>{{ object._source.date|date(\"d M Y\") }}</strong>\n	{% if object._source.artist  is defined %}\n		by <strong>{{ object._source.artist }}</strong>\n	{% endif %}\n	(see it on <a href=\"http://global.theus.be/fr/galleries/version/{{ object._id }}\" target=\"_blank\">GlobalView</a>)\n		<ul>\n			<li>Rating: \n				{% for i in 0..object._source.rating %}\n    					<i class=\"fa fa-fw fa-star\"></i>\n				{% endfor %}\n			</li>\n			<li>Name: {{ object._source.name }}</li>\n			<li>Pixel size: {{ object._source.pixel_size }}</li>\n			<li>Project: {{ object._source.project_name }}</li>\n			{% if object. _source.model  is defined %}\n				<li>Model: {{ object._source.model }}</li>\n			{% endif %}\n			{% if object. _source.lens_model  is defined %}\n				<li>Lens: {{ object._source.lens_model }}</li>\n			{% endif %}\n		</ul>\n\n	</div>\n</div>',1,NULL),
	(6,'2016-02-17 04:03:11','2016-02-17 04:03:11','task',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'tasks',NULL,NULL,'aperture',NULL,0,NULL),
	(7,'2016-02-17 05:11:37','2016-02-17 05:11:37','toto',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,1,'totos',NULL,NULL,'aperture',NULL,0,NULL);

/*!40000 ALTER TABLE `content_type` ENABLE KEYS */;
UNLOCK TABLES;


# Affichage de la table data_field
# ------------------------------------------------------------

DROP TABLE IF EXISTS `data_field`;

CREATE TABLE `data_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `integer_value` bigint(20) DEFAULT NULL,
  `float_value` double DEFAULT NULL,
  `date_value` datetime DEFAULT NULL,
  `text_value` longtext COLLATE utf8_unicode_ci,
  `sha1` varbinary(20) DEFAULT NULL,
  `language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `revision_id` int(11) DEFAULT NULL,
  `orderKey` int(11) NOT NULL,
  `field_type_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_154A89C71DFA7C8F` (`revision_id`),
  KEY `IDX_154A89C72B68A933` (`field_type_id`),
  KEY `IDX_154A89C7727ACA70` (`parent_id`),
  CONSTRAINT `FK_154A89C71DFA7C8F` FOREIGN KEY (`revision_id`) REFERENCES `revision` (`id`),
  CONSTRAINT `FK_154A89C72B68A933` FOREIGN KEY (`field_type_id`) REFERENCES `field_type` (`id`),
  CONSTRAINT `FK_154A89C7727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `data_field` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `data_field` WRITE;
/*!40000 ALTER TABLE `data_field` DISABLE KEYS */;

INSERT INTO `data_field` (`id`, `created`, `modified`, `integer_value`, `float_value`, `date_value`, `text_value`, `sha1`, `language`, `revision_id`, `orderKey`, `field_type_id`, `parent_id`)
VALUES
	(9,'2016-02-18 20:13:43','2016-02-18 20:13:43',NULL,NULL,NULL,NULL,NULL,NULL,55,1,1,NULL),
	(10,'2016-02-18 20:13:43','2016-02-18 20:13:43',NULL,NULL,NULL,'s',NULL,NULL,55,4,5,NULL),
	(11,'2016-02-18 20:13:43','2016-02-18 20:13:43',NULL,NULL,NULL,'v',NULL,NULL,NULL,2,2,10),
	(12,'2016-02-18 20:13:43','2016-02-18 20:13:43',NULL,NULL,NULL,'z',NULL,NULL,NULL,3,3,10),
	(13,'2016-02-18 20:13:43','2016-02-18 20:13:43',NULL,NULL,NULL,'s',NULL,NULL,NULL,4,4,10),
	(14,'2016-02-18 20:22:33','2016-02-18 20:22:33',NULL,NULL,NULL,NULL,NULL,NULL,56,1,1,NULL),
	(15,'2016-02-18 20:22:33','2016-02-18 20:22:33',NULL,NULL,NULL,'a',NULL,NULL,56,4,5,NULL),
	(16,'2016-02-18 20:22:33','2016-02-18 20:22:33',NULL,NULL,NULL,'s',NULL,NULL,NULL,2,2,15),
	(17,'2016-02-18 20:22:33','2016-02-18 20:22:33',NULL,NULL,NULL,'d',NULL,NULL,NULL,3,3,15),
	(18,'2016-02-18 20:22:33','2016-02-18 20:22:33',NULL,NULL,NULL,'f',NULL,NULL,NULL,4,4,15),
	(19,'2016-02-18 20:37:44','2016-02-18 20:37:44',NULL,NULL,NULL,NULL,NULL,NULL,57,1,1,NULL),
	(20,'2016-02-18 20:37:44','2016-02-18 20:37:44',NULL,NULL,NULL,NULL,NULL,NULL,57,4,5,NULL),
	(21,'2016-02-18 20:37:44','2016-02-18 20:37:44',NULL,NULL,NULL,'mdk',NULL,NULL,NULL,2,2,20),
	(22,'2016-02-18 20:37:44','2016-02-18 20:37:44',NULL,NULL,NULL,'mdk',NULL,NULL,NULL,3,3,20),
	(23,'2016-02-18 20:37:44','2016-02-18 20:37:44',NULL,NULL,NULL,'mdk',NULL,NULL,NULL,4,4,20),
	(24,'2016-02-18 20:38:30','2016-02-18 20:38:30',NULL,NULL,NULL,NULL,NULL,NULL,58,1,1,NULL),
	(25,'2016-02-18 20:38:30','2016-02-18 20:38:30',NULL,NULL,NULL,'s',NULL,NULL,58,4,5,NULL),
	(26,'2016-02-18 20:38:30','2016-02-18 20:38:30',NULL,NULL,NULL,'s',NULL,NULL,NULL,2,2,25),
	(27,'2016-02-18 20:38:30','2016-02-18 20:38:30',NULL,NULL,NULL,'s',NULL,NULL,NULL,3,3,25),
	(28,'2016-02-18 20:38:30','2016-02-18 20:38:30',NULL,NULL,NULL,'s',NULL,NULL,NULL,4,4,25),
	(29,'2016-02-18 22:41:24','2016-02-18 22:41:24',NULL,NULL,NULL,NULL,NULL,NULL,59,1,1,NULL),
	(30,'2016-02-18 22:41:24','2016-02-18 22:41:24',NULL,NULL,NULL,NULL,NULL,NULL,59,4,5,NULL),
	(31,'2016-02-18 22:41:24','2016-02-18 22:41:24',NULL,NULL,NULL,'Toto',NULL,NULL,NULL,2,2,30),
	(32,'2016-02-18 22:41:24','2016-02-18 22:41:24',NULL,NULL,NULL,'Toto',NULL,NULL,NULL,3,3,30),
	(33,'2016-02-18 22:41:24','2016-02-18 22:41:24',NULL,NULL,NULL,'Toto',NULL,NULL,NULL,4,4,30),
	(34,'2016-02-18 23:02:28','2016-02-18 23:02:28',NULL,NULL,NULL,NULL,NULL,NULL,60,1,1,NULL),
	(35,'2016-02-18 23:02:28','2016-02-18 23:02:28',NULL,NULL,NULL,NULL,NULL,NULL,60,4,5,NULL),
	(36,'2016-02-18 23:02:28','2016-02-18 23:02:28',NULL,NULL,NULL,'a',NULL,NULL,NULL,2,2,35),
	(37,'2016-02-18 23:02:28','2016-02-18 23:02:28',NULL,NULL,NULL,'b',NULL,NULL,NULL,3,3,35),
	(38,'2016-02-18 23:02:28','2016-02-18 23:02:28',NULL,NULL,NULL,'c',NULL,NULL,NULL,4,4,35);

/*!40000 ALTER TABLE `data_field` ENABLE KEYS */;
UNLOCK TABLES;


# Affichage de la table environment
# ------------------------------------------------------------

DROP TABLE IF EXISTS `environment`;

CREATE TABLE `environment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4626DE225E237E06` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `environment` WRITE;
/*!40000 ALTER TABLE `environment` DISABLE KEYS */;

INSERT INTO `environment` (`id`, `created`, `modified`, `name`)
VALUES
	(39,'2016-02-17 14:14:36','2016-02-17 14:14:36','aperture'),
	(40,'2016-02-17 14:16:53','2016-02-17 14:16:53','photos'),
	(41,'2016-02-18 20:22:11','2016-02-18 20:22:11','draft');

/*!40000 ALTER TABLE `environment` ENABLE KEYS */;
UNLOCK TABLES;


# Affichage de la table field_type
# ------------------------------------------------------------

DROP TABLE IF EXISTS `field_type`;

CREATE TABLE `field_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type_id` bigint(20) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `mapping` longtext COLLATE utf8_unicode_ci,
  `editOptions` longtext COLLATE utf8_unicode_ci,
  `viewOptions` longtext COLLATE utf8_unicode_ci,
  `orderKey` int(11) NOT NULL,
  `many` tinyint(1) NOT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9F123E931A445520` (`content_type_id`),
  KEY `IDX_9F123E93727ACA70` (`parent_id`),
  CONSTRAINT `FK_9F123E931A445520` FOREIGN KEY (`content_type_id`) REFERENCES `content_type` (`id`),
  CONSTRAINT `FK_9F123E93727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `field_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `field_type` WRITE;
/*!40000 ALTER TABLE `field_type` DISABLE KEYS */;

INSERT INTO `field_type` (`id`, `content_type_id`, `created`, `modified`, `type`, `name`, `deleted`, `description`, `mapping`, `editOptions`, `viewOptions`, `orderKey`, `many`, `label`, `parent_id`)
VALUES
	(1,2,'2016-01-01 00:00:00','2016-01-01 00:00:00','ouuid','key',0,NULL,NULL,NULL,NULL,1,0,'Key',NULL),
	(2,NULL,'2016-01-01 00:00:00','2016-01-01 00:00:00','string','value_fr',0,NULL,NULL,NULL,NULL,2,0,'Français',5),
	(3,NULL,'2016-01-01 00:00:00','2016-01-01 00:00:00','string','value_nl',0,NULL,NULL,NULL,NULL,3,0,'Nederlands',5),
	(4,NULL,'2016-01-01 00:00:00','2016-01-01 00:00:00','string','value_en',0,NULL,NULL,NULL,NULL,4,0,'English',5),
	(5,2,'2016-01-01 00:00:00','2016-01-01 00:00:00','container','translations',0,NULL,NULL,NULL,NULL,4,0,'Translations',NULL);

/*!40000 ALTER TABLE `field_type` ENABLE KEYS */;
UNLOCK TABLES;


# Affichage de la table revision
# ------------------------------------------------------------

DROP TABLE IF EXISTS `revision`;

CREATE TABLE `revision` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `ouuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `draft` tinyint(1) NOT NULL,
  `lock_by` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content_type_id` bigint(20) DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `lock_until` datetime DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tuple_index` (`end_time`,`ouuid`),
  KEY `IDX_6D6315CC1A445520` (`content_type_id`),
  CONSTRAINT `FK_6D6315CC1A445520` FOREIGN KEY (`content_type_id`) REFERENCES `content_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `revision` WRITE;
/*!40000 ALTER TABLE `revision` DISABLE KEYS */;

INSERT INTO `revision` (`id`, `created`, `modified`, `deleted`, `ouuid`, `start_time`, `draft`, `lock_by`, `content_type_id`, `end_time`, `lock_until`, `version`)
VALUES
	(47,'2016-02-18 19:10:50','2016-02-18 19:10:50',0,NULL,'2016-02-18 19:10:50',1,'admin',2,NULL,'2016-02-18 19:15:50',1),
	(48,'2016-02-18 19:12:29','2016-02-18 19:12:29',0,NULL,'2016-02-18 19:12:29',1,'admin',2,NULL,'2016-02-18 19:17:29',1),
	(49,'2016-02-18 19:20:45','2016-02-18 19:20:45',0,NULL,'2016-02-18 19:20:45',1,'admin',2,NULL,'2016-02-18 19:25:45',1),
	(50,'2016-02-18 19:34:47','2016-02-18 19:34:47',0,NULL,'2016-02-18 19:34:47',1,'admin',2,NULL,'2016-02-18 19:39:47',1),
	(51,'2016-02-18 19:57:14','2016-02-18 19:57:14',0,NULL,'2016-02-18 19:57:14',1,'admin',2,NULL,'2016-02-18 20:02:14',1),
	(52,'2016-02-18 19:59:03','2016-02-18 19:59:03',0,NULL,'2016-02-18 19:59:03',1,'admin',2,NULL,'2016-02-18 20:04:03',1),
	(53,'2016-02-18 20:01:54','2016-02-18 20:01:54',0,NULL,'2016-02-18 20:01:54',1,'admin',2,NULL,'2016-02-18 20:06:54',1),
	(54,'2016-02-18 20:05:43','2016-02-18 20:05:43',0,NULL,'2016-02-18 20:05:43',1,'admin',2,NULL,'2016-02-18 20:10:43',1),
	(55,'2016-02-18 20:08:00','2016-02-18 20:13:43',0,'AVL1zV8IgEQtjLergsxB','2016-02-18 20:08:00',0,'admin',2,NULL,'2016-02-18 20:13:00',2),
	(56,'2016-02-18 20:22:25','2016-02-18 20:22:33',0,'AVL11XVVgEQtjLergsxD','2016-02-18 20:22:25',0,'admin',2,NULL,'2016-02-18 20:27:25',2),
	(57,'2016-02-18 20:37:34','2016-02-18 20:37:34',0,'mdk','2016-02-18 20:37:34',1,'admin',2,NULL,'2016-02-18 20:42:34',1),
	(58,'2016-02-18 20:38:23','2016-02-18 20:46:56',0,'AVL16JYkgEQtjLergsxF','2016-02-18 20:38:23',0,'admin',2,NULL,'2016-02-18 20:43:23',5),
	(59,'2016-02-18 22:41:16','2016-02-18 22:41:28',0,'Toto','2016-02-18 22:41:16',0,'admin',2,NULL,'2016-02-18 22:46:16',3),
	(60,'2016-02-18 23:02:22','2016-02-18 23:02:28',0,'cool','2016-02-18 23:02:22',0,'admin',2,NULL,'2016-02-18 23:07:22',2);

/*!40000 ALTER TABLE `revision` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
