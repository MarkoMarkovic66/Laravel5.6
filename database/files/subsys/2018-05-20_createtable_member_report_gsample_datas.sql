
DROP TABLE IF EXISTS `member_report_gsample_datas`;

CREATE TABLE `member_report_gsample_datas` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`           int(11)      NOT NULL COMMENT '会員ID',
  `report_created_at` datetime     NULL DEFAULT NULL COMMENT 'レポート作成日',
  `g_index`           varchar(255) NULL DEFAULT NULL COMMENT 'g_index',
  `g_name`            varchar(255) NULL DEFAULT NULL COMMENT 'g_name',
  `lesson_time`       datetime     NULL DEFAULT NULL COMMENT 'レッスン日時',
  `tf_flag`           tinyint(1)   NULL DEFAULT '0'  COMMENT '0:False 1:True',
  `sentence`          text         NULL DEFAULT NULL COMMENT 'センテンス',
  `is_deleted`        tinyint(1)   DEFAULT '0',
  `created_at`        timestamp    NULL DEFAULT NULL,
  `updated_at`        timestamp    NULL DEFAULT NULL,
  `deleted_at`        timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX member_report_gsample_datas_idx01 (`user_id`, `report_created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


