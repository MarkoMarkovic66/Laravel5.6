
ALTER TABLE `user_outputs`
CHANGE COLUMN `category` `category` text NULL COMMENT 'カテゴリー',
CHANGE COLUMN `topic` `topic` text NULL COMMENT 'トピック';

ALTER TABLE `user_outputs`
ADD COLUMN `data_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1:sr_answer 2:user_lesson' AFTER `id`;


