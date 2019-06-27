
DROP TABLE IF EXISTS `batch_logs`;

CREATE TABLE `batch_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_name` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'バッチ名',
  `run_type` tinyint(1) DEFAULT '0' COMMENT '起動区分 1:自動実行 2:手動実行',
  `log_type` tinyint(1) DEFAULT '0' COMMENT 'ログ区分 1:開始ログ 2:終了ログ',
  `status` int(11) DEFAULT '0' COMMENT '処理ステータス 1:正常  2:異常',
  `event_at` datetime DEFAULT NULL COMMENT '起動/終了時刻',
  `result_message` text COLLATE utf8_unicode_ci COMMENT '実行結果メッセージ',
  `is_deleted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

