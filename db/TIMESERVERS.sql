-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- PoÄÃ­taÄ: localhost
-- VytvoÅeno: Ned 23. bÅe 2025, 16:32
-- Verze serveru: 10.5.8-MariaDB-log
-- Verze PHP: 8.2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- DatabÃ¡ze: `phpipam`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `timeservers`
--

CREATE TABLE `timeservers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timesrv1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `editDate` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/* insert default values */
INSERT INTO `timeservers` (`id`, `name`, `timesrv1`, `description`, `permissions`) VALUES
(1, 'Google NTP', 'time.google.com', 'Google public timeservers', '2;1');


ALTER TABLE `subnets` ADD `timeserverId` INT(11) NULL DEFAULT '0' AFTER `nameserverId`;