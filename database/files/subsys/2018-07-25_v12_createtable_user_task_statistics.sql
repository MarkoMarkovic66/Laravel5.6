-- タスク統計管理
DROP TABLE IF EXISTS `user_task_statistics`;

CREATE TABLE `user_task_statistics` (
  `id`                    int(11) NOT NULL AUTO_INCREMENT,
  `user_id`               int(11) NOT NULL COMMENT '会員ID',
  `alugo_user_id`         bigint(20)    NULL DEFAULT NULL COMMENT 'ALUGO会員ID',
  `counted_at`            datetime      NULL DEFAULT NULL COMMENT '集計実行日',
  `counted_since`         datetime      NULL DEFAULT NULL COMMENT '集計対象週 開始日',
  `counted_until`         datetime      NULL DEFAULT NULL COMMENT '集計対象週 終了日',
  `count_type`            tinyint(1)    NULL DEFAULT NULL COMMENT '集計値タイプ(1:セッション数 2:タスク数)',
  `session_num`           int(11)       NULL DEFAULT NULL COMMENT 'セッション数集計値',
  `task_type`             int(11)       NULL DEFAULT NULL COMMENT 'タスクタイプ(1:G 2:V 3:SR 4:復習 5以降:その他タスク)',
  `sent_task_count`       DECIMAL(6, 0) NULL DEFAULT NULL COMMENT '配信済みタスク集計値',
  `completed_task_count`  DECIMAL(6, 0) NULL DEFAULT NULL COMMENT '完了済みタスク集計値',
  `completed_task_rate`   DECIMAL(6, 2) NULL DEFAULT NULL COMMENT 'タスク消化率',
  `remark`        varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '備考',
  `is_deleted`    tinyint(1) NOT NULL DEFAULT '0',
  `created_at`    timestamp NULL DEFAULT NULL,
  `updated_at`    timestamp NULL DEFAULT NULL,
  `deleted_at`    timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_task_statistics_01` (`user_id`,`counted_since`,`counted_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='タスク統計管理';

