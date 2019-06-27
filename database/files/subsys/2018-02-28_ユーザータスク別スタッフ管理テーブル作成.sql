-- user_task_staffs
-- 該当会員の担当スタッフ(カウンセラー、オペレータ)を管理する
CREATE TABLE user_task_staffs (
  id            int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_task_id  int(11) NOT NULL COMMENT 'user_tasksのPK',
  staff_type    int(11) NOT NULL DEFAULT 0 COMMENT '1:カウンセラー 2:オペレータ',
  account_id    int(11) NOT NULL COMMENT 'accountsのPK',
  user_id       int(11) NOT NULL COMMENT 'usersのPK',
  is_deleted    tinyint(1) DEFAULT '0',
  created_at    timestamp NULL DEFAULT NULL,
  updated_at    timestamp NULL DEFAULT NULL,
  deleted_at    timestamp NULL DEFAULT NULL,
  INDEX idx_user_task_staffs_user_task_id(user_task_id),
  INDEX idx_user_task_staffs_account_id(account_id),
  INDEX idx_user_task_staffs_user_id(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



-- 下記項目は削除
ALTER TABLE user_tasks
DROP COLUMN `counselor_account_ids`,
DROP COLUMN `operator_account_ids`;

