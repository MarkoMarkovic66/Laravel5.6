ALTER TABLE `user_message_logs`
ADD COLUMN `cw_message_id` VARCHAR(40) NULL DEFAULT NULL COMMENT 'CWメッセージID' AFTER `topic_stuff_id`,
ADD COLUMN `cw_task_id`    VARCHAR(40) NULL DEFAULT NULL COMMENT 'CWタスクID'     AFTER `cw_message_id`;

ALTER TABLE `user_message_logs`
ADD INDEX `idx_user_message_logs_02` (`cw_message_id`);

