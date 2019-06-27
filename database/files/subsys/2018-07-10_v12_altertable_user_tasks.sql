
ALTER TABLE `user_tasks`
ADD COLUMN `is_completed` TINYINT(1) NULL DEFAULT 0 COMMENT 'タスク完了フラグ 0:未完了 1:完了' AFTER `comment`,
ADD COLUMN `completed_at` DATETIME NULL DEFAULT NULL COMMENT 'タスク完了日時' AFTER `is_completed`;


