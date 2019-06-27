-- phpMyAdmin SQL Dump
-- version 4.7.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 2018 年 3 月 14 日 01:52
-- サーバのバージョン： 5.7.17-0ubuntu0.16.04.2
-- PHP Version: 7.1.3-3+deb.sury.org~xenial+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `alue_subsys`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `user_outputs`
--

CREATE TABLE `user_outputs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT '会員ID',
  `activated_at` datetime DEFAULT NULL COMMENT '実施日',
  `category` int(11) DEFAULT NULL COMMENT 'カテゴリ',
  `topic` text COLLATE utf8_unicode_ci COMMENT 'トピック（レッスン）',
  `original_answer` text COLLATE utf8_unicode_ci COMMENT 'ユーザ回答',
  `revised_answer` text COLLATE utf8_unicode_ci COMMENT 'リバイス後回答',
  `comment` text COLLATE utf8_unicode_ci COMMENT '備考欄',
  `is_deleted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_outputs`
--
ALTER TABLE `user_outputs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_outputs`
--
ALTER TABLE `user_outputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
