ALTER TABLE `users`
ADD COLUMN `password` VARCHAR(255) NULL AFTER `mail`,
ADD COLUMN `remember_token` VARCHAR(100) NULL AFTER `password`;

