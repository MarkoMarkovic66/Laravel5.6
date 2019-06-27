ALTER TABLE `user_tasks`
ADD COLUMN `question_set_date` datetime NULL DEFAULT NULL COMMENT '出題日時' AFTER `user_task_status`,
ADD COLUMN `answer_deadline_date` datetime NULL DEFAULT NULL COMMENT '回答期限日時' AFTER `question_set_date`,
ADD COLUMN `answered_date` datetime NULL DEFAULT NULL COMMENT '回答日時' AFTER `answer_deadline_date`;
