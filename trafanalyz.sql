-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 05:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trafanalyz`
--

-- --------------------------------------------------------

--
-- Table structure for table `annotation`
--

CREATE TABLE `annotation` (
  `AnnotationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `UploadID` int(11) NOT NULL,
  `DataDate` date NOT NULL,
  `AnnotationText` text NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `column_mapping`
--

CREATE TABLE `column_mapping` (
  `MappingID` int(11) NOT NULL,
  `FormatID` int(11) NOT NULL,
  `CSVColumnName` varchar(255) NOT NULL,
  `SystemFieldName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `column_mapping`
--

INSERT INTO `column_mapping` (`MappingID`, `FormatID`, `CSVColumnName`, `SystemFieldName`) VALUES
(1, 1, 'Session primary channel group (Default channel group)', 'traffic_source'),
(2, 1, 'Sessions', 'visits'),
(3, 1, 'Engaged sessions', 'engaged_sessions'),
(4, 1, 'Engagement rate', 'bounce_rate'),
(5, 1, 'Average engagement time per session', 'avg_session_duration'),
(6, 1, 'Events per session', 'events_per_session'),
(7, 1, 'Event count', 'event_count'),
(8, 1, 'Key events', 'key_events'),
(9, 1, 'Session key event rate', 'session_key_event_rate'),
(10, 1, 'Total revenue', 'total_revenue');

-- --------------------------------------------------------

--
-- Table structure for table `comparison_file_link`
--

CREATE TABLE `comparison_file_link` (
  `ComparisonFileLinkID` int(11) NOT NULL,
  `ComparisonID` int(11) NOT NULL,
  `UploadID` int(11) NOT NULL,
  `FileOrder` int(11) NOT NULL CHECK (`FileOrder` in (1,2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `csv_format`
--

CREATE TABLE `csv_format` (
  `FormatID` int(11) NOT NULL,
  `AdminUserID` int(11) NOT NULL,
  `FormatName` varchar(255) NOT NULL,
  `ReportType` varchar(255) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `LastModifiedDate` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `csv_format`
--

INSERT INTO `csv_format` (`FormatID`, `AdminUserID`, `FormatName`, `ReportType`, `CreatedAt`, `LastModifiedDate`) VALUES
(1, 1, 'GA4 Traffic Acquisition', 'Session primary channel group (Default channel group)', '2025-05-06 22:47:12', '2025-05-06 22:47:12');

-- --------------------------------------------------------

--
-- Table structure for table `csv_upload`
--

CREATE TABLE `csv_upload` (
  `UploadID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `FileName` varchar(255) NOT NULL,
  `UploadDate` datetime NOT NULL DEFAULT current_timestamp(),
  `FileSize` int(11) NOT NULL,
  `IsValidated` tinyint(1) NOT NULL DEFAULT 0,
  `ReportType` varchar(255) NOT NULL,
  `DataDateStart` date NOT NULL,
  `DataDateEnd` date NOT NULL,
  `AccountName` varchar(255) DEFAULT NULL,
  `PropertyName` varchar(255) DEFAULT NULL,
  `IsSampleData` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `csv_upload`
--

INSERT INTO `csv_upload` (`UploadID`, `UserID`, `FileName`, `UploadDate`, `FileSize`, `IsValidated`, `ReportType`, `DataDateStart`, `DataDateEnd`, `AccountName`, `PropertyName`, `IsSampleData`) VALUES
(12, 1, 'manual_upload.csv', '2025-05-13 22:24:24', 0, 1, 'GA4 Traffic Acquisition', '2025-05-13', '2025-05-13', '', '', 0),
(13, 1, 'manual_upload.csv', '2025-05-13 22:28:05', 0, 1, 'GA4 Traffic Acquisition', '2025-05-13', '2025-05-13', '', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `export_history`
--

CREATE TABLE `export_history` (
  `ExportID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ExportTimestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `ExportType` varchar(50) NOT NULL,
  `ExportedDataDescription` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `metric_type`
--

CREATE TABLE `metric_type` (
  `MetricTypeID` int(11) NOT NULL,
  `MetricName` varchar(255) NOT NULL,
  `Description` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `metric_type`
--

INSERT INTO `metric_type` (`MetricTypeID`, `MetricName`, `Description`) VALUES
(1, 'Sessions', 'Number of sessions/visits'),
(2, 'Engaged sessions', 'Number of engaged sessions'),
(3, 'Engagement rate', 'Percentage of engaged sessions'),
(4, 'Average engagement time per session', 'Average time in seconds of engagement per session'),
(5, 'Events per session', 'Average number of events per session'),
(6, 'Event count', 'Total number of events'),
(7, 'Key events', 'Number of key events'),
(8, 'Session key event rate', 'Rate of sessions with key events'),
(9, 'Total revenue', 'Total revenue generated');

-- --------------------------------------------------------

--
-- Table structure for table `processed_data_point`
--

CREATE TABLE `processed_data_point` (
  `DataPointID` int(11) NOT NULL,
  `UploadID` int(11) NOT NULL,
  `SourceTypeID` int(11) NOT NULL,
  `MetricTypeID` int(11) NOT NULL,
  `DataDate` date NOT NULL,
  `Value` decimal(18,4) NOT NULL,
  `PeriodType` enum('Daily','Weekly','Monthly') DEFAULT 'Daily'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `processed_data_point`
--

INSERT INTO `processed_data_point` (`DataPointID`, `UploadID`, `SourceTypeID`, `MetricTypeID`, `DataDate`, `Value`, `PeriodType`) VALUES
(87, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(88, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(89, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(90, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(91, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(92, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(93, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(94, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(95, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(96, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(97, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(98, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(99, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(100, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(101, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(102, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(103, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(104, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(105, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(106, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(107, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(108, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(109, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(110, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(111, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(112, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(113, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(114, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(115, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(116, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(117, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(118, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(119, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(120, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(121, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(122, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(123, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(124, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(125, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(126, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(127, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(128, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(129, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(130, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(131, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(132, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(133, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(134, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(135, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(136, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(137, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(138, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(139, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(140, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(141, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(142, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(143, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(144, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(145, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(146, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(147, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(148, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(149, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(150, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(151, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(152, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(153, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(154, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(155, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(156, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(157, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(158, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(159, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(160, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(161, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(162, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(163, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(164, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(165, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(166, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(167, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(168, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(169, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(170, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(171, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(172, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(173, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(174, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(175, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(176, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(177, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(178, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(179, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(180, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(181, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(182, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(183, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(184, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(185, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(186, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(187, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(188, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(189, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(190, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(191, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(192, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(193, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(194, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(195, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(196, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(197, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(198, 13, 14, 1, '2025-05-13', 0.0000, 'Daily'),
(199, 13, 14, 1, '2025-05-13', 0.0000, 'Daily');

-- --------------------------------------------------------

--
-- Table structure for table `saved_comparison`
--

CREATE TABLE `saved_comparison` (
  `ComparisonID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ComparisonName` varchar(255) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `source_type`
--

CREATE TABLE `source_type` (
  `SourceTypeID` int(11) NOT NULL,
  `SourceName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `source_type`
--

INSERT INTO `source_type` (`SourceTypeID`, `SourceName`) VALUES
(9, ''),
(1, 'Direct'),
(5, 'Email'),
(12, 'Group Name'),
(13, 'Mr'),
(11, 'Ms'),
(2, 'Organic Search'),
(3, 'Paid Search'),
(8, 'Project title chosen:'),
(6, 'Referral'),
(10, 'Saluation(Mr/Ms)'),
(4, 'Social'),
(7, 'Unassigned'),
(14, 'Unknown');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` enum('Admin','End-User') NOT NULL DEFAULT 'End-User',
  `AccountStatus` enum('Active','Suspended') NOT NULL DEFAULT 'Active',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `Username`, `Email`, `PasswordHash`, `Role`, `AccountStatus`, `CreatedAt`) VALUES
(1, 'System Admin', 'admin@trafanalyz.com', '$2y$10$YourHashedPasswordHere', 'Admin', 'Active', '2025-05-06 22:47:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `annotation`
--
ALTER TABLE `annotation`
  ADD PRIMARY KEY (`AnnotationID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `UploadID` (`UploadID`),
  ADD KEY `DataDate` (`DataDate`);

--
-- Indexes for table `column_mapping`
--
ALTER TABLE `column_mapping`
  ADD PRIMARY KEY (`MappingID`),
  ADD UNIQUE KEY `FormatID` (`FormatID`,`CSVColumnName`);

--
-- Indexes for table `comparison_file_link`
--
ALTER TABLE `comparison_file_link`
  ADD PRIMARY KEY (`ComparisonFileLinkID`),
  ADD UNIQUE KEY `ComparisonID` (`ComparisonID`,`FileOrder`),
  ADD KEY `UploadID` (`UploadID`);

--
-- Indexes for table `csv_format`
--
ALTER TABLE `csv_format`
  ADD PRIMARY KEY (`FormatID`),
  ADD UNIQUE KEY `FormatName` (`FormatName`),
  ADD KEY `AdminUserID` (`AdminUserID`);

--
-- Indexes for table `csv_upload`
--
ALTER TABLE `csv_upload`
  ADD PRIMARY KEY (`UploadID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `export_history`
--
ALTER TABLE `export_history`
  ADD PRIMARY KEY (`ExportID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `metric_type`
--
ALTER TABLE `metric_type`
  ADD PRIMARY KEY (`MetricTypeID`),
  ADD UNIQUE KEY `MetricName` (`MetricName`);

--
-- Indexes for table `processed_data_point`
--
ALTER TABLE `processed_data_point`
  ADD PRIMARY KEY (`DataPointID`),
  ADD KEY `SourceTypeID` (`SourceTypeID`),
  ADD KEY `MetricTypeID` (`MetricTypeID`),
  ADD KEY `DataDate` (`DataDate`),
  ADD KEY `UploadID` (`UploadID`,`SourceTypeID`,`MetricTypeID`);

--
-- Indexes for table `saved_comparison`
--
ALTER TABLE `saved_comparison`
  ADD PRIMARY KEY (`ComparisonID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `source_type`
--
ALTER TABLE `source_type`
  ADD PRIMARY KEY (`SourceTypeID`),
  ADD UNIQUE KEY `SourceName` (`SourceName`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `annotation`
--
ALTER TABLE `annotation`
  MODIFY `AnnotationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `column_mapping`
--
ALTER TABLE `column_mapping`
  MODIFY `MappingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `comparison_file_link`
--
ALTER TABLE `comparison_file_link`
  MODIFY `ComparisonFileLinkID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `csv_format`
--
ALTER TABLE `csv_format`
  MODIFY `FormatID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `csv_upload`
--
ALTER TABLE `csv_upload`
  MODIFY `UploadID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `export_history`
--
ALTER TABLE `export_history`
  MODIFY `ExportID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `metric_type`
--
ALTER TABLE `metric_type`
  MODIFY `MetricTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `processed_data_point`
--
ALTER TABLE `processed_data_point`
  MODIFY `DataPointID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `saved_comparison`
--
ALTER TABLE `saved_comparison`
  MODIFY `ComparisonID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `source_type`
--
ALTER TABLE `source_type`
  MODIFY `SourceTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `annotation`
--
ALTER TABLE `annotation`
  ADD CONSTRAINT `annotation_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`),
  ADD CONSTRAINT `annotation_ibfk_2` FOREIGN KEY (`UploadID`) REFERENCES `csv_upload` (`UploadID`);

--
-- Constraints for table `column_mapping`
--
ALTER TABLE `column_mapping`
  ADD CONSTRAINT `column_mapping_ibfk_1` FOREIGN KEY (`FormatID`) REFERENCES `csv_format` (`FormatID`);

--
-- Constraints for table `comparison_file_link`
--
ALTER TABLE `comparison_file_link`
  ADD CONSTRAINT `comparison_file_link_ibfk_1` FOREIGN KEY (`ComparisonID`) REFERENCES `saved_comparison` (`ComparisonID`),
  ADD CONSTRAINT `comparison_file_link_ibfk_2` FOREIGN KEY (`UploadID`) REFERENCES `csv_upload` (`UploadID`);

--
-- Constraints for table `csv_format`
--
ALTER TABLE `csv_format`
  ADD CONSTRAINT `csv_format_ibfk_1` FOREIGN KEY (`AdminUserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `csv_upload`
--
ALTER TABLE `csv_upload`
  ADD CONSTRAINT `csv_upload_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `export_history`
--
ALTER TABLE `export_history`
  ADD CONSTRAINT `export_history_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);

--
-- Constraints for table `processed_data_point`
--
ALTER TABLE `processed_data_point`
  ADD CONSTRAINT `processed_data_point_ibfk_1` FOREIGN KEY (`UploadID`) REFERENCES `csv_upload` (`UploadID`),
  ADD CONSTRAINT `processed_data_point_ibfk_2` FOREIGN KEY (`SourceTypeID`) REFERENCES `source_type` (`SourceTypeID`),
  ADD CONSTRAINT `processed_data_point_ibfk_3` FOREIGN KEY (`MetricTypeID`) REFERENCES `metric_type` (`MetricTypeID`);

--
-- Constraints for table `saved_comparison`
--
ALTER TABLE `saved_comparison`
  ADD CONSTRAINT `saved_comparison_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
