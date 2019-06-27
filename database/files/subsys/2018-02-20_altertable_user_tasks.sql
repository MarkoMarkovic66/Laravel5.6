ALTER TABLE `user_tasks`
ADD COLUMN `user_task_status` int(11) NOT NULL DEFAULT 0 AFTER `task_id`;

