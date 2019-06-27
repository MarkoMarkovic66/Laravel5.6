
DROP TABLE IF EXISTS `batch_commands`;

CREATE TABLE `batch_commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `command_name` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'バッチコマンド名',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

