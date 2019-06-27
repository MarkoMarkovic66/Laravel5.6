
ALTER TABLE `batch_logs`
ADD COLUMN `run_type` TINYINT(1) NULL DEFAULT 0 COMMENT '実行区分 1:実行 2:終了 3:再実行' AFTER `batch_name`;

ALTER TABLE `batch_logs`
CHANGE COLUMN `latest_retried_at` `event_at` DATETIME NULL COMMENT '起動/終了時刻';

