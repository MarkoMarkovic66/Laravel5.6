-- レッスン消化数管理
DROP TABLE IF EXISTS `user_lesson_progresses`;

CREATE TABLE `user_lesson_progresses` (
  `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`               INT(11)       NOT NULL          COMMENT '会員ID',
  `alugo_user_id`         BIGINT(20)    NULL DEFAULT NULL COMMENT 'ALUGO会員ID',
  `counted_at`            DATETIME      NULL DEFAULT NULL COMMENT '計数日時',
  `contract_term_days`    DECIMAL(4, 0) NULL DEFAULT NULL COMMENT '受講期間契約日数',
  `elapsed_days`          DECIMAL(4, 0) NULL DEFAULT NULL COMMENT '受講開始からの経過日数',
  `tickets_total`         DECIMAL(4, 0) NULL DEFAULT NULL COMMENT '全チケット枚数',
  `tickets_used`          DECIMAL(4, 0) NULL DEFAULT NULL COMMENT '消化済みチケット枚数',
  `tickets_used_rate`     DECIMAL(6, 2) NULL DEFAULT NULL COMMENT 'チケット消化率',
  `remark`                VARCHAR(255)  NULL DEFAULT NULL COMMENT '備考',
  `is_deleted`            TINYINT(1)    NOT NULL DEFAULT '0',
  `created_at`            TIMESTAMP     NULL DEFAULT NULL,
  `updated_at`            TIMESTAMP     NULL DEFAULT NULL,
  `deleted_at`            TIMESTAMP     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_lesson_progresses_01` (`user_id`, `counted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT 'レッスン消化数管理';

