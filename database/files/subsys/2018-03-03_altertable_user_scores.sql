ALTER TABLE `user_scores`
CHANGE COLUMN `alugo_user_id`   `alugo_user_id` bigint(20) NULL COMMENT 'alugo会員ID',
CHANGE COLUMN `count`           `count` int(11) NULL DEFAULT '0' COMMENT 'アセスメント回数',
CHANGE COLUMN `level`           `level` int(11) NULL DEFAULT '0' COMMENT 'レベル';

