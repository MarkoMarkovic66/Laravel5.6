
DROP TABLE IF EXISTS `user_plans`;

CREATE TABLE `user_plans` (
  `id`                int(11)     NOT NULL AUTO_INCREMENT,
  `user_id` 					int(11) 		NOT NULL 					COMMENT '会員ID',
  `alugo_user_id`     bigint(20)  NULL DEFAULT NULL COMMENT 'ALUGO会員ID',
  `goods_id`          int(11)     NULL DEFAULT NULL COMMENT 'goodsId',
  `package_type`      int(11)     NULL DEFAULT NULL COMMENT 'packageType',
  `package_start_at`  datetime    NULL DEFAULT NULL COMMENT 'packageStartAt',
  `package_end_at`    datetime    NULL DEFAULT NULL COMMENT 'packageEndAt',
  `lesson_start_at`   datetime    NULL DEFAULT NULL COMMENT 'lessonStartAt',
  `lesson_end_at`     datetime    NULL DEFAULT NULL COMMENT 'lessonEndAt',
  `lesson_ticket`     int(11)     NULL DEFAULT NULL COMMENT 'lessonTicket',
  `assessment_ticket` int(11)     NULL DEFAULT NULL COMMENT 'assessmentTicket',
  `counseling_ticket` int(11)     NULL DEFAULT NULL COMMENT 'counselingTicket',
  `option_goal`       int(11)     NULL DEFAULT NULL COMMENT 'optionGoal',
  `option_review`     int(11)     NULL DEFAULT NULL COMMENT 'optionReview',
  `counseling_wday`   int(11)     NULL DEFAULT NULL COMMENT 'counselingWday',
  `counseling_hour`   varchar(20) NULL DEFAULT NULL COLLATE utf8_unicode_ci COMMENT 'counselingHour',
  `counseler_id`      int(11)     NULL DEFAULT NULL COMMENT 'counselerId',
  `schedule`          text        NULL DEFAULT NULL COLLATE utf8_unicode_ci COMMENT 'schedule',
  `purchase_id`       int(11)     NULL DEFAULT NULL COMMENT 'purchaseId',
  `is_deleted`        tinyint(1)  NOT NULL DEFAULT '0',
  `created_at`        timestamp   NULL DEFAULT NULL,
  `updated_at`        timestamp   NULL DEFAULT NULL,
  `deleted_at`        timestamp   NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_plans_01` (`user_id`),
  KEY `idx_user_plans_02` (`alugo_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

