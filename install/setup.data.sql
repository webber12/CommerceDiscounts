CREATE TABLE `{PREFIX}commerce_discounts` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_start` date DEFAULT NULL,
  `date_finish` date DEFAULT NULL,
  `discount_type` int(2) NOT NULL DEFAULT '0' COMMENT '1 - category, 2 - products, 3 - tv relations, 4 - cart',
  `user_group` int(5) NOT NULL DEFAULT '-1',
  `elements` text,
  `info` text,
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_summ` decimal(10,2) NOT NULL DEFAULT '0.00',
  `active` int(2) NOT NULL DEFAULT '0',
  `menuindex` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;