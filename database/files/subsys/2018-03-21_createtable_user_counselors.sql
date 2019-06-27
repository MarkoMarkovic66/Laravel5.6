
CREATE TABLE `user_counselors` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL COMMENT '会員ID',
 `account_id` int(11) NOT NULL COMMENT '担当カウンセラーID',
 `is_deleted` tinyint(1) DEFAULT '0',
 `created_at` timestamp NULL DEFAULT NULL,
 `updated_at` timestamp NULL DEFAULT NULL,
 `deleted_at` timestamp NULL DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



