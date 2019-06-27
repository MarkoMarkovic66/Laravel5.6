-- chatwork回答関連

-- user_tasks
ALTER TABLE `user_tasks`
ADD COLUMN `user_message_log_id` int(11) NULL COMMENT '回答レコードid' AFTER `answered_date`;


-- user_message_logs
ALTER TABLE `user_message_logs`
ADD COLUMN `user_task_linked` int(11) NOT NULL DEFAULT 0 COMMENT '回答紐付け済みフラグ 0:未 1:済' AFTER `posted_context`;

