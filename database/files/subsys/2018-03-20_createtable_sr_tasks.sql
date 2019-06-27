
CREATE TABLE `sr_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sr_context` text NULL COMMENT 'SR出題内容',
  `is_deleted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


