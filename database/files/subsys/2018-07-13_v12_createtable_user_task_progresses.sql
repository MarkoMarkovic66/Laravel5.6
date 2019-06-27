-- 出題タスク消化進捗管理
DROP TABLE IF EXISTS `user_task_progresses`;

CREATE TABLE `user_task_progresses` (
  `id`                   INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`              INT(11)       NOT NULL          COMMENT '会員ID',
  `alugo_user_id`        bigint(20)    NULL DEFAULT NULL COMMENT 'ALUGO会員ID',
  `counted_at`           DATETIME      NULL DEFAULT NULL COMMENT '計数日時',
  `task_type`            INT(11)       NULL DEFAULT NULL COMMENT 'タスク種別',
  `sent_task_count`      DECIMAL(6, 0) NULL DEFAULT NULL COMMENT '配信済みタスク累積数',
  `completed_task_count` DECIMAL(6, 0) NULL DEFAULT NULL COMMENT '完了済みタスク累積数',
  `completed_task_rate`  DECIMAL(6, 2) NULL DEFAULT NULL COMMENT 'タスク消化率',
  `remark`               VARCHAR(255)  NULL DEFAULT NULL COMMENT '備考',
  `is_deleted`           TINYINT(1)    NOT NULL DEFAULT '0',
  `created_at`           TIMESTAMP     NULL DEFAULT NULL,
  `updated_at`           TIMESTAMP     NULL DEFAULT NULL,
  `deleted_at`           TIMESTAMP     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_task_progresses_01` (`user_id`, `counted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT '出題タスク消化進捗管理';


