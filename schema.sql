DROP TABLE IF EXISTS `comp`;
CREATE TABLE `comp` (
  `id` int NOT NULL,
  `cod` varchar(17) NOT NULL DEFAULT '',
  `artikul` varchar(10) NOT NULL,
  `idglava` int NOT NULL DEFAULT '0',
  `idcaption` int NOT NULL DEFAULT '0',
  `idtype` int NOT NULL DEFAULT '0',
  `nickname` varchar(50) NOT NULL,
  `namedoc` varchar(50) NOT NULL,
  `name` text NOT NULL,
  `slogan` text NOT NULL,
  `description` text NOT NULL,
  `name_ua` varchar(150) NOT NULL,
  `name_en` varchar(150) NOT NULL,
  `description_ua` text NOT NULL,
  `description_en` text NOT NULL,
  `full_description` text NOT NULL,
  `dt` varchar(12) NOT NULL DEFAULT '',
  `pay` float(12,2) NOT NULL DEFAULT '0.00',
  `profitpay` float(12,2) NOT NULL,
  `firstpage` char(2) NOT NULL DEFAULT '',
  `flag` char(2) NOT NULL DEFAULT '',
  `action` char(2) NOT NULL DEFAULT '',
  `hit` char(1) NOT NULL,
  `web` varchar(1) NOT NULL,
  `nfoto` varchar(100) NOT NULL DEFAULT '',
  `nfoto1` varchar(100) NOT NULL DEFAULT '',
  `nfoto2` varchar(100) NOT NULL DEFAULT '',
  `nfoto3` varchar(100) NOT NULL,
  `nfoto4` varchar(100) NOT NULL,
  `nfoto5` varchar(100) NOT NULL,
  `nfoto6` varchar(100) NOT NULL,
  `nfoto7` varchar(100) NOT NULL,
  `nfoto8` varchar(100) NOT NULL,
  `nfoto9` varchar(100) NOT NULL,
  `nvideo1` varchar(100) NOT NULL,
  `nvideo2` varchar(100) NOT NULL,
  `pay1` float(12,2) NOT NULL DEFAULT '0.00',
  `pay2` float(12,2) NOT NULL DEFAULT '0.00',
  `nfile` varchar(100) NOT NULL DEFAULT '',
  `garant` smallint NOT NULL DEFAULT '0',
  `top` int NOT NULL DEFAULT '0',
  `top5` int NOT NULL,
  `upd` int NOT NULL DEFAULT '1',
  `sklad` smallint NOT NULL DEFAULT '1',
  `hand` smallint NOT NULL DEFAULT '0',
  `idsklad` int NOT NULL DEFAULT '0',
  `firma` varchar(50) NOT NULL DEFAULT '0',
  `user` varchar(30) NOT NULL DEFAULT '',
  `htmlname` text NOT NULL,
  `htmldescr` text NOT NULL,
  `htmlkeys` text NOT NULL,
  `htmlkeyspop` text NOT NULL,
  `constanta` char(1) NOT NULL,
  `firma_share` varchar(10) NOT NULL,
  `param1` int NOT NULL,
  `param2` int NOT NULL,
  `param3` int NOT NULL,
  `param4` int NOT NULL,
  `param5` int NOT NULL,
  `param6` int NOT NULL,
  `paramfix1` char(1) NOT NULL,
  `paramfix2` char(1) NOT NULL,
  `paramfix3` char(1) NOT NULL,
  `paramfix4` char(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `conf`;
CREATE TABLE `conf` (
  `id` int NOT NULL,
  `type` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `value` float(12,2) NOT NULL,
  `value1` float(12,2) NOT NULL,
  `value2` float(12,2) NOT NULL,
  `firma` int NOT NULL,
  `users` int NOT NULL,
  `vision` char(1) NOT NULL DEFAULT '1',
  `status` int NOT NULL,
  `hide` smallint NOT NULL,
  `doc` varchar(50) NOT NULL,
  `color` varchar(9) NOT NULL,
  `constanta` char(1) NOT NULL,
  `descript` text NOT NULL,
  `descript2` text NOT NULL,
  `descript3` text NOT NULL,
  `descript4` text NOT NULL,
  `descript5` text NOT NULL,
  `work` char(1) NOT NULL,
  `first` tinyint NOT NULL,
  `userid` int NOT NULL,
  `htmlkeys` text NOT NULL,
  `registr` smallint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `descript`;
CREATE TABLE `descript` (
  `id` int NOT NULL,
  `pnum` int NOT NULL,
  `firma` int NOT NULL,
  `descript` int NOT NULL,
  `descript2` int NOT NULL,
  `descript3` int NOT NULL,
  `descript4` int NOT NULL,
  `descript5` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `name_ua` varchar(150) NOT NULL,
  `name_en` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `description_ua` text NOT NULL,
  `description_en` text NOT NULL,
  `web` char(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `docs`;
CREATE TABLE `docs` (
  `id` int NOT NULL,
  `name` varchar(30) NOT NULL DEFAULT '',
  `summa` float(12,2) NOT NULL DEFAULT '0.00',
  `firma` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `document`;
CREATE TABLE `document` (
  `id` int unsigned NOT NULL,
  `data` varchar(14) NOT NULL DEFAULT '0',
  `data2` varchar(14) NOT NULL,
  `time` time NOT NULL,
  `time2` time NOT NULL,
  `num` int NOT NULL DEFAULT '0',
  `client1` int NOT NULL DEFAULT '0',
  `client2` int unsigned NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `type` varchar(5) NOT NULL DEFAULT '',
  `summa` float(12,2) NOT NULL DEFAULT '0.00',
  `summa2` float(12,2) NOT NULL,
  `summa3` float(12,2) NOT NULL,
  `discount` float(4,2) NOT NULL DEFAULT '0.00',
  `firma` int NOT NULL DEFAULT '0',
  `user` varchar(20) NOT NULL DEFAULT '0',
  `schet` int unsigned NOT NULL DEFAULT '0',
  `provodka` varchar(5) NOT NULL DEFAULT '0',
  `close` int NOT NULL DEFAULT '0',
  `numz` int NOT NULL,
  `typez` varchar(6) NOT NULL,
  `dt` int NOT NULL DEFAULT '0',
  `dt2` int NOT NULL,
  `status` varchar(50) NOT NULL,
  `money` varchar(50) NOT NULL,
  `docum` varchar(250) NOT NULL,
  `ttn` varchar(20) NOT NULL,
  `sms_flag` char(1) NOT NULL,
  `reteil` varchar(50) NOT NULL,
  `oplata` varchar(50) NOT NULL,
  `oplata2` varchar(50) NOT NULL,
  `sklads` varchar(50) NOT NULL,
  `typeproduct` varchar(50) NOT NULL,
  `reestr` varchar(50) NOT NULL,
  `manager` varchar(50) NOT NULL,
  `dostup` int NOT NULL,
  `docid` int NOT NULL,
  `bonus` float NOT NULL,
  `work` char(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `field`;
CREATE TABLE `field` (
  `id` int NOT NULL,
  `idkeyfield` int NOT NULL DEFAULT '0',
  `keyfield` varchar(50) NOT NULL DEFAULT '',
  `val` varchar(50) NOT NULL DEFAULT '',
  `valua` varchar(50) NOT NULL,
  `valen` varchar(50) NOT NULL,
  `description` varchar(200) NOT NULL,
  `descriptionua` varchar(200) NOT NULL,
  `descriptionen` varchar(200) NOT NULL,
  `link` varchar(35) NOT NULL,
  `links` text NOT NULL,
  `nw` int NOT NULL DEFAULT '0',
  `upd` int NOT NULL DEFAULT '0',
  `num` smallint NOT NULL DEFAULT '0',
  `pers` float(12,2) NOT NULL DEFAULT '0.00',
  `pers1` float(12,2) NOT NULL DEFAULT '0.00',
  `pers2` float(12,2) NOT NULL DEFAULT '0.00',
  `firma` int NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `hkeys` text NOT NULL,
  `hdescr` text NOT NULL,
  `visible` tinyint(1) NOT NULL,
  `firstpage` tinyint(1) NOT NULL,
  `foto1` varchar(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `filter`;
CREATE TABLE `filter` (
  `id` int NOT NULL,
  `idkeyfield` int NOT NULL,
  `idfilter` int NOT NULL,
  `keyfield` varchar(30) NOT NULL,
  `val` varchar(60) NOT NULL DEFAULT '',
  `valru` varchar(60) NOT NULL,
  `valen` varchar(60) NOT NULL,
  `description` text NOT NULL,
  `descriptionen` text NOT NULL,
  `descriptionru` text NOT NULL,
  `count` int NOT NULL DEFAULT '0',
  `top` int NOT NULL,
  `num` smallint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `firma`;
CREATE TABLE `firma` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `regnum` varchar(12) NOT NULL DEFAULT '',
  `inn` varchar(15) NOT NULL,
  `schet` varchar(30) NOT NULL DEFAULT '',
  `bank` varchar(50) NOT NULL DEFAULT '',
  `mfo` varchar(6) NOT NULL DEFAULT '',
  `town` varchar(25) NOT NULL DEFAULT '',
  `address` varchar(50) NOT NULL DEFAULT '',
  `map` varchar(200) NOT NULL,
  `view` varchar(15) NOT NULL DEFAULT '',
  `phone` varchar(50) NOT NULL DEFAULT '',
  `dwn` int NOT NULL DEFAULT '0',
  `data` date NOT NULL DEFAULT '0000-00-00',
  `userid` int NOT NULL,
  `firma` int NOT NULL,
  `direktor` varchar(30) NOT NULL,
  `pidpys` varchar(30) NOT NULL,
  `pechat` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `kurs`;
CREATE TABLE `kurs` (
  `id` int NOT NULL,
  `kurs` float(6,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `money`;
CREATE TABLE `money` (
  `id` int NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `summa` float(12,2) NOT NULL DEFAULT '0.00',
  `firma` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` int NOT NULL,
  `title` text NOT NULL,
  `title_ua` text NOT NULL,
  `title_en` text NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `kratko` text NOT NULL,
  `kratko_ua` text NOT NULL,
  `kratko_en` text NOT NULL,
  `txt` text NOT NULL,
  `txt_ua` text NOT NULL,
  `txt_en` text NOT NULL,
  `htmlkeys` text NOT NULL,
  `tags` text NOT NULL,
  `codesocnet` text NOT NULL,
  `dt` varchar(12) NOT NULL,
  `time` time DEFAULT NULL,
  `firma` int NOT NULL,
  `foto` varchar(250) NOT NULL,
  `foto2` varchar(250) NOT NULL,
  `foto3` varchar(250) NOT NULL,
  `foto4` varchar(250) NOT NULL,
  `field` int NOT NULL,
  `field1` int NOT NULL,
  `hot` tinyint(1) NOT NULL,
  `view` tinyint(1) NOT NULL,
  `always` tinyint(1) NOT NULL,
  `article` tinyint(1) NOT NULL,
  `author` int NOT NULL,
  `top` varchar(5) NOT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `news_firma_url_index` (`firma`,`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `oplata`;
CREATE TABLE `oplata` (
  `id` int NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `summa` float NOT NULL,
  `firma` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `price`;
CREATE TABLE `price` (
  `id` int NOT NULL,
  `pnum` varchar(15) NOT NULL DEFAULT '0',
  `firma` int NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `description` text NOT NULL,
  `pay1` float(12,2) NOT NULL DEFAULT '0.00',
  `pay` float(12,2) NOT NULL DEFAULT '0.00',
  `oldpay` float(12,2) NOT NULL,
  `garant` char(2) NOT NULL DEFAULT '',
  `sklad` int NOT NULL DEFAULT '0',
  `data` varchar(12) NOT NULL DEFAULT '',
  `count` float(12,3) NOT NULL DEFAULT '0.000',
  `tgroup` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `price_sklad`;
CREATE TABLE `price_sklad` (
  `id` int NOT NULL,
  `pnum` varchar(15) NOT NULL DEFAULT '0',
  `firma` int NOT NULL DEFAULT '0',
  `garant` char(2) NOT NULL DEFAULT '',
  `sklad` int NOT NULL DEFAULT '0',
  `count` float(12,3) NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `project`;
CREATE TABLE `project` (
  `id` int NOT NULL,
  `num` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `url` text NOT NULL,
  `telegram` text NOT NULL,
  `instagram` text NOT NULL,
  `twitter` text NOT NULL,
  `facebook` text NOT NULL,
  `userid` int NOT NULL,
  `foto` varchar(50) NOT NULL,
  `foto_header` varchar(50) NOT NULL,
  `foto_footer` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `web` tinyint(1) NOT NULL,
  `hit` tinyint(1) NOT NULL,
  `htmlkeys` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `protokol`;
CREATE TABLE `protokol` (
  `id` int NOT NULL,
  `conf` int NOT NULL DEFAULT '0',
  `lang` varchar(5) NOT NULL,
  `value` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `reestr`;
CREATE TABLE `reestr` (
  `id` int NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `firma` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `reklama`;
CREATE TABLE `reklama` (
  `id` int NOT NULL,
  `name` varchar(30) NOT NULL DEFAULT '',
  `firma` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `reteil`;
CREATE TABLE `reteil` (
  `id` int NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `firma` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `sklad`;
CREATE TABLE `sklad` (
  `id` int NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `color` varchar(25) NOT NULL,
  `firma` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `sklads`;
CREATE TABLE `sklads` (
  `id` int NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `address` varchar(100) NOT NULL,
  `firma` int NOT NULL DEFAULT '0',
  `userid` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int NOT NULL,
  `name` varchar(25) NOT NULL DEFAULT '',
  `iduser` int NOT NULL,
  `firma` int NOT NULL DEFAULT '0',
  `address` varchar(30) NOT NULL DEFAULT '',
  `phone` varchar(50) NOT NULL DEFAULT '',
  `login` varchar(30) NOT NULL DEFAULT '',
  `pass` varchar(50) NOT NULL DEFAULT '',
  `status` int NOT NULL,
  `kassa` int NOT NULL,
  `sklad` int NOT NULL,
  `website` varchar(250) NOT NULL,
  `bonus` float NOT NULL,
  `summa` float NOT NULL,
  `userid` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `users_cashe`;
CREATE TABLE `users_cashe` (
  `id` int NOT NULL,
  `firma` int NOT NULL,
  `user_id` int NOT NULL,
  `top` int NOT NULL,
  `doc` varchar(20) NOT NULL,
  `data` varchar(30) NOT NULL,
  `num` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `z_basket`;
CREATE TABLE `z_basket` (
  `id` int unsigned NOT NULL,
  `login` varchar(25) NOT NULL DEFAULT '',
  `cod` varchar(17) NOT NULL DEFAULT '',
  `ch` varchar(10) NOT NULL DEFAULT '',
  `count` int unsigned DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `z_body`;
CREATE TABLE `z_body` (
  `id` int unsigned NOT NULL,
  `docnum` int unsigned NOT NULL DEFAULT '0',
  `pid` int unsigned NOT NULL DEFAULT '0',
  `pnum` int unsigned NOT NULL DEFAULT '0',
  `pcod` varchar(25) NOT NULL DEFAULT '',
  `pprice` float(12,2) NOT NULL DEFAULT '0.00',
  `pcount` float(12,3) NOT NULL DEFAULT '1.000',
  `psumma` float(12,2) NOT NULL DEFAULT '0.00',
  `pgarant` smallint NOT NULL DEFAULT '0',
  `type` varchar(50) NOT NULL DEFAULT '',
  `pname` varchar(250) NOT NULL DEFAULT '',
  `firma` int NOT NULL,
  `zvalue` varchar(200) NOT NULL,
  `docid` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `z_document`;
CREATE TABLE `z_document` (
  `id` int unsigned NOT NULL,
  `data` varchar(14) NOT NULL DEFAULT '0',
  `data2` varchar(14) NOT NULL,
  `time` time NOT NULL,
  `num` int NOT NULL DEFAULT '0',
  `client1` int NOT NULL DEFAULT '0',
  `client2` int unsigned NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `type` varchar(5) NOT NULL DEFAULT '',
  `summa` float(12,2) NOT NULL DEFAULT '0.00',
  `summa2` float NOT NULL,
  `summa3` float(12,2) NOT NULL,
  `discount` float(4,2) NOT NULL DEFAULT '0.00',
  `firma` int NOT NULL DEFAULT '0',
  `user` varchar(20) NOT NULL DEFAULT '0',
  `schet` int unsigned NOT NULL DEFAULT '0',
  `provodka` varchar(5) NOT NULL DEFAULT '0',
  `close` int NOT NULL DEFAULT '0',
  `dt` int NOT NULL DEFAULT '0',
  `dt2` int NOT NULL,
  `numz` int NOT NULL,
  `typez` varchar(10) NOT NULL,
  `status` varchar(50) NOT NULL,
  `money` varchar(50) NOT NULL,
  `reteil` varchar(50) NOT NULL,
  `docum` text NOT NULL,
  `sms_flag` char(1) NOT NULL,
  `oplata` varchar(50) NOT NULL,
  `oplata2` varchar(50) NOT NULL,
  `sklads` varchar(50) NOT NULL,
  `typeproduct` varchar(50) NOT NULL,
  `reestr` varchar(50) NOT NULL,
  `manager` varchar(50) NOT NULL,
  `dostup` int NOT NULL,
  `docid` int NOT NULL,
  `bonus` float NOT NULL,
  `work` char(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `z_price`;
CREATE TABLE `z_price` (
  `id` int NOT NULL,
  `cod` varchar(15) NOT NULL DEFAULT '',
  `idagent` int NOT NULL DEFAULT '0',
  `code` varchar(10) NOT NULL DEFAULT '',
  `name` text NOT NULL,
  `description` text NOT NULL,
  `dilpay` float(12,2) NOT NULL DEFAULT '0.00',
  `pay` float(12,2) NOT NULL DEFAULT '0.00',
  `garant` char(2) NOT NULL DEFAULT '',
  `sklad` smallint NOT NULL DEFAULT '0',
  `dt` varchar(12) NOT NULL DEFAULT '',
  `upd` int NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
DROP TABLE IF EXISTS `zd_document`;
CREATE TABLE `zd_document` (
  `id` int unsigned NOT NULL,
  `data` varchar(14) NOT NULL DEFAULT '0',
  `data2` varchar(14) NOT NULL,
  `time` time NOT NULL,
  `time2` text NOT NULL,
  `num` int NOT NULL DEFAULT '0',
  `client1` int NOT NULL DEFAULT '0',
  `client2` int unsigned NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `type` varchar(5) NOT NULL DEFAULT '',
  `summa` float(12,2) NOT NULL DEFAULT '0.00',
  `summa2` float NOT NULL,
  `discount` float(4,2) NOT NULL DEFAULT '0.00',
  `firma` int NOT NULL DEFAULT '0',
  `user` varchar(20) NOT NULL DEFAULT '0',
  `schet` int unsigned NOT NULL DEFAULT '0',
  `provodka` varchar(5) NOT NULL DEFAULT '0',
  `close` int NOT NULL DEFAULT '0',
  `dt` int NOT NULL DEFAULT '0',
  `dt2` int NOT NULL,
  `numz` int NOT NULL,
  `typez` varchar(10) NOT NULL,
  `status` varchar(50) NOT NULL,
  `money` varchar(50) NOT NULL,
  `reteil` varchar(50) NOT NULL,
  `docum` varchar(220) NOT NULL,
  `sms_flag` char(1) NOT NULL,
  `oplata` varchar(50) NOT NULL,
  `oplata2` varchar(50) NOT NULL,
  `sklads` varchar(50) NOT NULL,
  `typeproduct` varchar(50) NOT NULL,
  `reestr` varchar(50) NOT NULL,
  `manager` varchar(50) NOT NULL,
  `dostup` int NOT NULL,
  `docid` int NOT NULL,
  `bonus` float NOT NULL,
  `work` char(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
