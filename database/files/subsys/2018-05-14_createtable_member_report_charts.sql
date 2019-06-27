
DROP TABLE IF EXISTS `member_report_charts`;

CREATE TABLE `member_report_charts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '会員ID',
  `report_created_at` datetime NULL DEFAULT NULL COMMENT 'レポート作成日',
  `chart_type` tinyint(1) NULL DEFAULT NULL COMMENT 'グラフ画像種別 1:グラフ1 ... 7:グラフ7',
  `chart_order` tinyint(1) NULL DEFAULT NULL COMMENT '表示順',
  `chart_title` varchar(255) NULL DEFAULT NULL COMMENT 'グラフ名(タイトル)',
  `chart_s3_key` text NULL DEFAULT NULL COMMENT 'グラフ画像S3キー',
  `chart_remark` text NULL DEFAULT NULL COMMENT '備考',
  `chart_message` text NULL DEFAULT NULL COMMENT '会員へのメッセージ',
  `is_deleted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX member_report_charts_idx01 (`user_id`, `report_created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

