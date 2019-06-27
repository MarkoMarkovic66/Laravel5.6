
ALTER TABLE `batch_logs`
ADD COLUMN `result_message` text NULL COMMENT '実行結果メッセージ' AFTER `event_at`;


