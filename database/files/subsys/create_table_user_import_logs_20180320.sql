CREATE TABLE `csv_import_logs` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `file_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'ファイル種別（1: users、2: companies）',
 `file_name` text NOT NULL COMMENT 'ファイル名',
 `result` tinyint(4) NOT NULL DEFAULT '0' COMMENT '結果（1: success、0: failure）',
 `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
 `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `modified` datetime DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8