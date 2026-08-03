-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Aug 03, 2026 at 11:33 PM
-- Server version: 5.7.39
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `magritte`
--
CREATE DATABASE IF NOT EXISTS `magritte` DEFAULT CHARACTER SET latin1 COLLATE latin1_general_ci;
USE `magritte`;

-- --------------------------------------------------------

--
-- Table structure for table `pipelines`
--

DROP TABLE IF EXISTS `pipelines`;
CREATE TABLE `pipelines` (
  `id` int(11) NOT NULL,
  `name` varchar(128) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `short_name` varchar(48) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `description` tinytext COLLATE latin1_general_ci,
  `enabled` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_items`
--

DROP TABLE IF EXISTS `pipeline_items`;
CREATE TABLE `pipeline_items` (
  `id` int(11) NOT NULL,
  `name` varchar(24) COLLATE latin1_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_items_pipelines`
--

DROP TABLE IF EXISTS `pipeline_items_pipelines`;
CREATE TABLE `pipeline_items_pipelines` (
  `id` int(11) NOT NULL,
  `pipeline_item_id` int(11) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `stage` varchar(48) COLLATE latin1_general_ci DEFAULT NULL,
  `status` enum('idle','running','failed') COLLATE latin1_general_ci NOT NULL DEFAULT 'idle',
  `last_run_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_stages`
--

DROP TABLE IF EXISTS `pipeline_stages`;
CREATE TABLE `pipeline_stages` (
  `id` int(11) NOT NULL,
  `pipeline_id` int(11) DEFAULT NULL,
  `name` varchar(128) COLLATE latin1_general_ci NOT NULL,
  `short_name` varchar(48) COLLATE latin1_general_ci NOT NULL,
  `task` varchar(128) COLLATE latin1_general_ci DEFAULT NULL,
  `args` varchar(128) COLLATE latin1_general_ci DEFAULT NULL,
  `task_args` varchar(128) COLLATE latin1_general_ci DEFAULT NULL,
  `runs_empty` tinyint(1) NOT NULL DEFAULT '0',
  `next_stage_id` int(11) DEFAULT NULL,
  `promotion_enabled` tinyint(1) DEFAULT NULL,
  `layout` text COLLATE latin1_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pipelines`
--
ALTER TABLE `pipelines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `short_name` (`short_name`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `updated_at` (`updated_at`);

--
-- Indexes for table `pipeline_items`
--
ALTER TABLE `pipeline_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `updated_at` (`updated_at`);

--
-- Indexes for table `pipeline_items_pipelines`
--
ALTER TABLE `pipeline_items_pipelines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pipeline_id_pipeline_item_id` (`pipeline_item_id`,`pipeline_id`),
  ADD KEY `pipeline_item_id` (`pipeline_item_id`),
  ADD KEY `pipeline_id` (`pipeline_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `updated_at` (`updated_at`),
  ADD KEY `stage` (`stage`),
  ADD KEY `status` (`status`),
  ADD KEY `last_run_at` (`last_run_at`);

--
-- Indexes for table `pipeline_stages`
--
ALTER TABLE `pipeline_stages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `updated_at` (`updated_at`),
  ADD KEY `pipeline_id` (`pipeline_id`),
  ADD KEY `task` (`task`),
  ADD KEY `next_stage_id` (`next_stage_id`),
  ADD KEY `short_name` (`short_name`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pipelines`
--
ALTER TABLE `pipelines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_items`
--
ALTER TABLE `pipeline_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_items_pipelines`
--
ALTER TABLE `pipeline_items_pipelines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_stages`
--
ALTER TABLE `pipeline_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pipeline_items_pipelines`
--
ALTER TABLE `pipeline_items_pipelines`
  ADD CONSTRAINT `pipeline_items_pipelines_ibfk_1` FOREIGN KEY (`pipeline_item_id`) REFERENCES `pipeline_items` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `pipeline_items_pipelines_ibfk_2` FOREIGN KEY (`pipeline_id`) REFERENCES `pipelines` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `pipeline_stages`
--
ALTER TABLE `pipeline_stages`
  ADD CONSTRAINT `pipeline_stages_ibfk_1` FOREIGN KEY (`pipeline_id`) REFERENCES `pipelines` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `pipeline_stages_ibfk_2` FOREIGN KEY (`next_stage_id`) REFERENCES `pipeline_stages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;
