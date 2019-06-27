
DROP TABLE IF EXISTS `member_report_grammar_rates`;

CREATE TABLE `member_report_grammar_rates` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`           int(11)      NOT NULL COMMENT '会員ID',
  `report_created_at` datetime     NULL DEFAULT NULL COMMENT 'レポート作成日',
  `g_index`           varchar(255) NULL DEFAULT NULL COMMENT 'g_index',
  `g_name`            varchar(255) NULL DEFAULT NULL COMMENT 'g_name',
  `latest_value`      decimal(5,1) NULL DEFAULT NULL COMMENT '今回数値',
  `previous_value`    decimal(5,1) NULL DEFAULT NULL COMMENT '前回数値',
  `is_deleted`        tinyint(1)   DEFAULT '0',
  `created_at`        timestamp    NULL DEFAULT NULL,
  `updated_at`        timestamp    NULL DEFAULT NULL,
  `deleted_at`        timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX member_report_grammar_rates_idx01 (`user_id`, `report_created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


