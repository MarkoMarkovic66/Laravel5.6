-- 学習方針カテゴリマスタ
DROP TABLE IF EXISTS `lp_categories`;

CREATE TABLE `lp_categories` (
  `id`            	int(11) NOT NULL AUTO_INCREMENT,
  `category_layer`	int(11) NULL DEFAULT NULL COMMENT 'カテゴリ階層(1,2...)',
  `parent_id`      	int(11) NULL DEFAULT NULL COMMENT '親カテゴリid',
  `order_no`      	int(11) NULL DEFAULT NULL COMMENT '同一階層内の並び順',
  `category_name`   text COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'カテゴリ名称',
  `remark`        	varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '備考',
  `is_deleted`    	tinyint(1) NOT NULL DEFAULT '0',
  `created_at`    	timestamp NULL DEFAULT NULL,
  `updated_at`    	timestamp NULL DEFAULT NULL,
  `deleted_at`    	timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='学習方針カテゴリマスタ';

INSERT INTO `lp_categories` VALUES 
(1,1,0,1,'学習方針カテゴリ1','',0,'2018-07-26 04:00:00',NULL,NULL),
(2,1,0,2,'学習方針カテゴリ2','',0,'2018-07-26 04:00:00',NULL,NULL),
(3,1,0,3,'学習方針カテゴリ3','',0,'2018-07-26 04:00:00',NULL,NULL),
(4,2,1,1,'学習方針カテゴリ1-1','',0,'2018-07-26 04:00:00',NULL,NULL),
(5,2,1,2,'学習方針カテゴリ1-2','',0,'2018-07-26 04:00:00',NULL,NULL),
(6,2,2,1,'学習方針カテゴリ2-1','',0,'2018-07-26 04:00:00',NULL,NULL);


-- 学習方針テーブル変更
ALTER TABLE `user_learning_policies`
CHANGE COLUMN `lp_category_id` `lp_category_id1` INT(11) NULL DEFAULT NULL COMMENT 'カテゴリ大id',
ADD COLUMN `lp_category_id2` INT(11) NULL DEFAULT NULL COMMENT 'カテゴリ大id' AFTER `lp_category_id1`,
ADD COLUMN `tag_name` TEXT COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'タグ文字列' AFTER `lp_category_id2`;


-- -------------------------------------------------------------------
-- 以下は廃止
-- -------------------------------------------------------------------
-- 学習方針タグ管理
DROP TABLE IF EXISTS `user_lp_properties`;

-- CREATE TABLE `user_lp_properties` (
--   `id`            					int(11) NOT NULL AUTO_INCREMENT,
--   `user_id`       					int(11) NOT NULL COMMENT '会員ID',
--   `user_learning_policy_id` int(11) NOT NULL COMMENT '学習方針レコードID',
--   `tag_type`      					tinyint(1) NOT NULL COMMENT 'タグタイプ(1:カテゴリ 2:タグ)',
--   `category_id_layer1` 			int(11) NULL DEFAULT NULL COMMENT 'カテゴリ大id',
--   `category_id_layer2` 			int(11) NULL DEFAULT NULL COMMENT 'カテゴリ小id',
--   `tag_name`      					text COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'タグ文字列',
--   `remark`        					varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '備考',
--   `is_deleted`    					tinyint(1) NOT NULL DEFAULT '0',
--   `created_at`    					timestamp NULL DEFAULT NULL,
--   `updated_at`    					timestamp NULL DEFAULT NULL,
--   `deleted_at`    					timestamp NULL DEFAULT NULL,
--   PRIMARY KEY (`id`),
--   KEY `idx_user_lp_properties_01` (`user_id`),
--   KEY `idx_user_lp_properties_02` (`user_learning_policy_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='学習方針タグ管理';
-- 


