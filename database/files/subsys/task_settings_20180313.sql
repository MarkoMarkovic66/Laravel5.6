-- phpMyAdmin SQL Dump
-- version 4.7.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 2018 年 3 月 14 日 03:19
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
-- テーブルの構造 `task_settings`
--

CREATE TABLE `task_settings` (
  `id` int(11) NOT NULL,
  `assessment_item` varchar(256) COLLATE utf8_unicode_ci NOT NULL COMMENT 'アセスメント項目',
  `grade` varchar(256) COLLATE utf8_unicode_ci NOT NULL COMMENT 'グレード',
  `day_number` tinyint(4) NOT NULL COMMENT '曜日番号',
  `task_type` int(11) NOT NULL COMMENT '課題種別',
  `other_task_id` int(11) DEFAULT NULL COMMENT 'その他宿題ID',
  `is_deleted` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- テーブルのデータのダンプ `task_settings`
--

INSERT INTO `task_settings` (`id`, `assessment_item`, `grade`, `day_number`, `task_type`, `other_task_id`, `is_deleted`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Attitude', 'E以上', 1, 4, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(2, 'Attitude', 'E以上', 2, 4, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(3, 'Attitude', 'E以上', 3, 4, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(4, 'Attitude', 'E以上', 4, 4, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(5, 'Attitude', 'E以上', 5, 4, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(6, 'Attitude', 'E以上', 6, 4, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(7, 'Attitude', 'E以上', 7, 4, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(8, 'Attitude 2', 'E以上', 1, 1, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(9, 'Attitude 2', 'E以上', 2, 2, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(10, 'Attitude 2', 'E以上', 3, 1, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(11, 'Attitude 2', 'E以上', 4, 2, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(12, 'Attitude 2', 'E以上', 5, 1, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(13, 'Attitude 2', 'E以上', 6, 2, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(14, 'Attitude 2', 'E以上', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(15, 'Speaking', 'E', 1, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(16, 'Speaking', 'E', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(17, 'Speaking', 'E', 3, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(18, 'Speaking', 'E', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(19, 'Speaking', 'E', 5, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(20, 'Speaking', 'E', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(21, 'Speaking', 'E', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(22, 'Speaking', 'D', 1, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(23, 'Speaking', 'D', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(24, 'Speaking', 'D', 3, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(25, 'Speaking', 'D', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(26, 'Speaking', 'D', 5, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(27, 'Speaking', 'D', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(28, 'Speaking', 'D', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(29, 'Speaking', 'C', 1, 3, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(30, 'Speaking', 'C', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(31, 'Speaking', 'C', 3, 3, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(32, 'Speaking', 'C', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(33, 'Speaking', 'C', 5, 3, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(34, 'Speaking', 'C', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(35, 'Speaking', 'C', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(36, 'Speaking', 'B', 1, 3, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(37, 'Speaking', 'B', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(38, 'Speaking', 'B', 3, 3, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(39, 'Speaking', 'B', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(40, 'Speaking', 'B', 5, 3, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(41, 'Speaking', 'B', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(42, 'Speaking', 'B', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(43, 'Speaking', 'A', 1, 3, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(44, 'Speaking', 'A', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(45, 'Speaking', 'A', 3, 3, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(46, 'Speaking', 'A', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(47, 'Speaking', 'A', 5, 3, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(48, 'Speaking', 'A', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(49, 'Speaking', 'A', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(50, 'Listening', 'E', 1, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(51, 'Listening', 'E', 2, 5, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(52, 'Listening', 'E', 3, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(53, 'Listening', 'E', 4, 5, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(54, 'Listening', 'E', 5, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(55, 'Listening', 'E', 6, 5, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(56, 'Listening', 'E', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(57, 'Listening', 'D', 1, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(58, 'Listening', 'D', 2, 5, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(59, 'Listening', 'D', 3, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(60, 'Listening', 'D', 4, 5, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(61, 'Listening', 'D', 5, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(62, 'Listening', 'D', 6, 5, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(63, 'Listening', 'D', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(64, 'Listening', 'C', 1, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(65, 'Listening', 'C', 2, 6, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(66, 'Listening', 'C', 3, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(67, 'Listening', 'C', 4, 6, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(68, 'Listening', 'C', 5, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(69, 'Listening', 'C', 6, 6, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(70, 'Listening', 'C', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(71, 'Listening', 'B', 1, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(72, 'Listening', 'B', 2, 7, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(73, 'Listening', 'B', 3, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(74, 'Listening', 'B', 4, 7, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(75, 'Listening', 'B', 5, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(76, 'Listening', 'B', 6, 7, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(77, 'Listening', 'B', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(78, 'Listening', 'A', 1, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(79, 'Listening', 'A', 2, 7, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(80, 'Listening', 'A', 3, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(81, 'Listening', 'A', 4, 7, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(82, 'Listening', 'A', 5, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(83, 'Listening', 'A', 6, 7, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(84, 'Listening', 'A', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(85, 'Grammar', 'E', 1, 8, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(86, 'Grammar', 'E', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(87, 'Grammar', 'E', 3, 8, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(88, 'Grammar', 'E', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(89, 'Grammar', 'E', 5, 8, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(90, 'Grammar', 'E', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(91, 'Grammar', 'E', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(92, 'Grammar', 'D', 1, 9, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(93, 'Grammar', 'D', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(94, 'Grammar', 'D', 3, 9, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(95, 'Grammar', 'D', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(96, 'Grammar', 'D', 5, 9, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(97, 'Grammar', 'D', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(98, 'Grammar', 'D', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(99, 'Grammar', 'C', 1, 10, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(100, 'Grammar', 'C', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(101, 'Grammar', 'C', 3, 10, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(102, 'Grammar', 'C', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(103, 'Grammar', 'C', 5, 10, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(104, 'Grammar', 'C', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(105, 'Grammar', 'C', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(106, 'Grammar', 'B', 1, 11, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(107, 'Grammar', 'B', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(108, 'Grammar', 'B', 3, 11, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(109, 'Grammar', 'B', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(110, 'Grammar', 'B', 5, 11, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(111, 'Grammar', 'B', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(112, 'Grammar', 'B', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(113, 'Grammar', 'A', 1, 12, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(114, 'Grammar', 'A', 2, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(115, 'Grammar', 'A', 3, 12, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(116, 'Grammar', 'A', 4, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(117, 'Grammar', 'A', 5, 12, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(118, 'Grammar', 'A', 6, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL),
(119, 'Grammar', 'A', 7, 0, 0, 0, '2018-02-25 15:00:00', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `task_settings`
--
ALTER TABLE `task_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `task_settings`
--
ALTER TABLE `task_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
