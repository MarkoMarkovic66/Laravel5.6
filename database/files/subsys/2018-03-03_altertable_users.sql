
ALTER TABLE `users`
CHANGE COLUMN `account_type` `user_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'ユーザータイプ';


ALTER TABLE `users`
CHANGE COLUMN `first_name`      `first_name`    varchar(256) COLLATE utf8_unicode_ci NULL COMMENT '名',
CHANGE COLUMN `last_name`       `last_name`     varchar(256) COLLATE utf8_unicode_ci NULL COMMENT '氏',
CHANGE COLUMN `first_name_en`   `first_name_en` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '名英語表記',
CHANGE COLUMN `last_name_en`    `last_name_en`  varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '氏英語表記',
CHANGE COLUMN `mail`            `mail`          varchar(256) COLLATE utf8_unicode_ci NULL COMMENT 'メールアドレス',
CHANGE COLUMN `phone_number`    `phone_number`  varchar(128) COLLATE utf8_unicode_ci NULL COMMENT '電話番号',
CHANGE COLUMN `curriculum_id`   `curriculum_id` bigint(20) 	NULL DEFAULT '0' COMMENT '修了カリキュラムID',
CHANGE COLUMN `study_sec`       `study_sec`     int(11) 		NULL DEFAULT '0' COMMENT '学習時間（秒）',
CHANGE COLUMN `status`          `status`        tinyint(4) 	NULL COMMENT 'ステータス',
CHANGE COLUMN `matrix`          `matrix`        text COLLATE utf8_unicode_ci NULL COMMENT 'カリキュラムマトリクス';




