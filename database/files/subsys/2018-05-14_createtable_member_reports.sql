
DROP TABLE IF EXISTS `member_reports`;

CREATE TABLE `member_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '会員ID',
  `report_created_at` datetime NULL DEFAULT NULL COMMENT 'レポート作成日',

  `arugo_user_id` bigint(20) NULL DEFAULT NULL COMMENT 'ALUGO会員ID',
  `contract_period_since` datetime NULL DEFAULT NULL COMMENT 'ご契約期間since',
  `contract_period_until` datetime NULL DEFAULT NULL COMMENT 'ご契約期間until',
  `lesson_period_since` datetime NULL DEFAULT NULL COMMENT 'レッスン受講期間since',
  `lesson_period_until` datetime NULL DEFAULT NULL COMMENT 'レッスン受講期間until',

  `report_name` varchar(255) NULL DEFAULT NULL COMMENT 'レポート名',
  `report_s3_key` text NULL DEFAULT NULL COMMENT 'レポートS3キー',
  `report_remark` text NULL DEFAULT NULL COMMENT '備考',
  `report_message` text NULL DEFAULT NULL COMMENT '会員へのメッセージ',
  `is_posted` tinyint(1) NULL DEFAULT '0' COMMENT '会員への送信済フラグ 0:未送信 1:送信済み',
  `posted_at` datetime NULL DEFAULT NULL COMMENT '会員への送信日',

  `is_deleted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX member_reports_idx01 (`user_id`, `report_created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

