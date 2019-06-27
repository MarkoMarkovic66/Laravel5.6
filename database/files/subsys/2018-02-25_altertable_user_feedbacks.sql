ALTER TABLE `user_feedbacks`
ADD COLUMN `user_task_id` int(11) NOT NULL COMMENT 'user_taskのPK' AFTER `user_id`;
