-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2023 at 01:15 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inmacom`
--

-- --------------------------------------------------------

--
-- Table structure for table `dam_levels`
--

CREATE TABLE `dam_levels` (
  `id` int(11) NOT NULL,
  `station_id` varchar(50) NOT NULL,
  `fsc` decimal(10,2) NOT NULL DEFAULT 0.00,
  `storage` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `dam_levels`
--

INSERT INTO `dam_levels` (`id`, `station_id`, `fsc`, `storage`, `date`) VALUES
(1, '41', '23.00', '23.00', '2023-03-10 13:19:55');

-- --------------------------------------------------------

--
-- Table structure for table `flow_levels`
--

CREATE TABLE `flow_levels` (
  `id` int(11) NOT NULL,
  `station_id` varchar(50) NOT NULL,
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(25) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `flow_levels`
--

INSERT INTO `flow_levels` (`id`, `station_id`, `value`, `unit`, `date`) VALUES
(1, 'KOB008', '27.50', 'm^3/s ', '2023-03-08');

-- --------------------------------------------------------

--
-- Table structure for table `groundwater`
--

CREATE TABLE `groundwater` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `value` int(11) NOT NULL,
  `unit` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rainfall`
--

CREATE TABLE `rainfall` (
  `id` int(11) NOT NULL,
  `station_id` varchar(50) NOT NULL,
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `station`
--

CREATE TABLE `station` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `latitude` decimal(10,5) NOT NULL,
  `longitude` decimal(10,5) NOT NULL,
  `category` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `station`
--

INSERT INTO `station` (`id`, `code`, `name`, `latitude`, `longitude`, `category`) VALUES
(41, '41', 'Pongolapoort', '-27.44209', '31.98376', 'Dam Levels'),
(42, '42', 'Bivane', '-27.51875', '31.02708', 'Dam Levels'),
(43, '43', 'Heyhope', '-27.00069', '30.51598', 'Dam Levels'),
(44, '44', 'Lubovane', '-26.74374', '31.69375', 'Dam Levels'),
(45, '45', 'Morgenstond', '-26.72061', '30.53542', 'Dam Levels'),
(46, '46', 'Luphohlo', '-26.39374', '31.10208', 'Dam Levels'),
(47, '47', 'Maguga', '-26.06041', '31.25208', 'Dam Levels'),
(48, '48', 'Driekoppies', '-25.76614', '31.48802', 'Dam Levels'),
(49, '49', 'Vygeboom', '-25.85624', '30.62292', 'Dam Levels'),
(50, '50', 'Nooitgedacht', '-25.96607', '30.07164', 'Dam Levels'),
(206, 'E-173', 'Xinavane', '-25.73333', '32.68167', 'Flow Levels'),
(207, 'E-23', 'Ressano Garcia', '-25.43750', '31.99056', 'Flow Levels'),
(208, 'E-28', 'Manhica', '-25.61278', '32.67222', 'Flow Levels'),
(209, 'E-4', 'Maputo em Salamanga', '-26.47000', '32.66333', 'Flow Levels'),
(210, 'E-413', 'Incoluane', '-25.31667', '32.29167', 'Flow Levels'),
(211, 'E-564', 'Maragra', '-25.47250', '32.81000', 'Flow Levels'),
(212, 'E-572', 'Marracuene', '-25.00139', '32.73333', 'Flow Levels'),
(213, 'E-6', 'Maputo em Madubula I', '-26.79000', '32.44000', 'Flow Levels'),
(214, 'E-630', 'Barragem Corrumane', '-25.05333', '32.13250', 'Flow Levels'),
(215, 'E-634', 'Barragem Corrumane - Jusante', '-25.21583', '32.13611', 'Flow Levels'),
(216, '16', 'Usuthu', '-26.80622', '32.00110', 'Flow Levels'),
(217, '21', 'Ngwempisi', '-26.74140', '30.80196', 'Flow Levels'),
(218, 'GS25', 'Mkhondvo', '-27.07167', '31.04809', 'Flow Levels'),
(219, 'KOB007', 'Mananga', '-25.94741', '31.74810', 'Flow Levels'),
(220, '33', 'Lusushwana', '-26.30504', '30.91603', 'Flow Levels'),
(221, 'KOB008', 'Matsamo', '-25.75546', '31.44687', 'Flow Levels'),
(222, 'GS8', 'Ngwavuma', '-27.08821', '31.79695', 'Flow Levels'),
(223, 'W4H013', 'Pongolapoort Dam Outflow', '-27.42266', '32.08047', 'Flow Levels'),
(224, 'X1H001', 'Komati at Hooggenoeg', '-26.03617', '30.99761', 'Flow Levels'),
(225, 'X1H007', 'Diepgezet', '-26.00056', '31.06645', 'Flow Levels'),
(226, 'X1H049', 'Driekoppies Dam outflow', '-25.71085', '31.53368', 'Flow Levels'),
(227, 'X2H016', 'Crocodile', '-25.36386', '31.95572', 'Flow Levels'),
(228, 'X2H036', 'Komati at Komatipoort', '-25.43661', '31.98244', 'Flow Levels'),
(229, 'X3H015', 'Sabie at Lower Sabie', '-25.14953', '31.94067', 'Flow Levels'),
(230, 'U-26', 'Mahamba Boarder Gate (R543)', '-27.06519', '30.99356', 'Water Quality'),
(231, 'U-44', 'R33 Road Bridge to Amsterdam', '-26.67981', '30.70253', 'Water Quality'),
(232, 'U-53', 'Nerston Border Gate', '-26.51305', '30.78633', 'Water Quality'),
(233, 'U-57', 'Mpuluzi Oxidation Ponds', '-26.32367', '30.80501', 'Water Quality'),
(234, 'U-61', 'Lusushwana River Bridge', '-26.26522', '30.90338', 'Water Quality'),
(235, 'CRL-39', 'Ekulindeni Bridge', '-26.02847', '31.05479', 'Water Quality'),
(236, 'K-25', 'Driekopies Dam', '-25.71362', '31.53326', 'Water Quality'),
(237, 'K-13', 'Mananga Border gate', '-25.93218', '31.76018', 'Water Quality'),
(238, 'K-2', 'komati River Below Komati Chalets', '-25.44322', '31.96417', 'Water Quality'),
(239, 'C-72', 'Komatipoort gold course', '-25.43789', '31.97369', 'Water Quality'),
(240, 'SS-51', 'Lower Sabie Rest Camp', '-25.12134', '31.92466', 'Water Quality');

-- --------------------------------------------------------

--
-- Table structure for table `treshhold`
--

CREATE TABLE `treshhold` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `value` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `organization` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'Data Manager',
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `organization`, `role`, `status`) VALUES
(1, 'Buyani', 'Fakudze', 'test@inmacom.net', '12345', 'INMACOM', 'Admin', 'Inactive'),
(2, 'Sibusiso', 'Baartjies', 'test1@inmacom.net', '12345', 'Datamatics', 'Data Manager', 'Active'),
(3, 'Thokozani', 'Ginindza', 'test2@inmacom.net', '12345', 'Datamatics', 'Data Manager', 'Active'),
(4, 'Coordinator', 'Gwebu', 'test3@inmacom.net', '12345', 'Datamatics', 'Data Manager', 'Active');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_dam_levels`
-- (See below for the actual view)
--
CREATE TABLE `view_dam_levels` (
`id` int(11)
,`code` varchar(20)
,`name` varchar(100)
,`latitude` decimal(10,5)
,`longitude` decimal(10,5)
,`category` varchar(100)
,`dam_levels_id` int(11)
,`fsc` decimal(10,2)
,`storage` decimal(10,2)
,`date` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_flow_levels`
-- (See below for the actual view)
--
CREATE TABLE `view_flow_levels` (
`id` int(11)
,`code` varchar(20)
,`name` varchar(100)
,`latitude` decimal(10,5)
,`longitude` decimal(10,5)
,`category` varchar(100)
,`flow_levels_id` int(11)
,`value` decimal(10,2)
,`unit` varchar(25)
,`date` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_rainfall`
-- (See below for the actual view)
--
CREATE TABLE `view_rainfall` (
`id` int(11)
,`code` varchar(20)
,`name` varchar(100)
,`latitude` decimal(10,5)
,`longitude` decimal(10,5)
,`category` varchar(100)
,`value` decimal(10,2)
,`unit` varchar(11)
,`date` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_water_quality`
-- (See below for the actual view)
--
CREATE TABLE `view_water_quality` (
`id` int(11)
,`code` varchar(20)
,`name` varchar(100)
,`latitude` decimal(10,5)
,`longitude` decimal(10,5)
,`category` varchar(100)
,`value` decimal(10,2)
,`unit` varchar(50)
,`date` datetime
);

-- --------------------------------------------------------

--
-- Table structure for table `water_quality`
--

CREATE TABLE `water_quality` (
  `id` int(11) NOT NULL,
  `station_id` varchar(50) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `value` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `water_quality`
--

INSERT INTO `water_quality` (`id`, `station_id`, `date`, `value`, `unit`) VALUES
(3, '231', '2023-02-21 00:13:54', '56.00', '0.00'),
(5, '230', '2023-02-21 00:20:32', '12.00', '0.00'),
(6, '232', '2023-02-21 00:20:42', '89.00', '0.00'),
(7, '233', '2023-02-21 00:20:55', '90.00', '0.00'),
(8, '234', '2023-02-21 00:21:07', '45.00', '0.00'),
(9, '235', '2023-02-21 00:21:19', '78.00', '0.00'),
(10, '236', '2023-02-21 00:21:30', '56.00', '0.00'),
(11, '237', '2023-02-21 00:21:45', '87.00', '0.00'),
(12, '238', '2023-02-21 00:21:54', '34.00', '0.00'),
(13, '239', '2023-02-21 00:22:03', '22.00', '0.00'),
(14, '240', '2023-02-21 00:22:13', '88.00', '0.00');

-- --------------------------------------------------------

--
-- Structure for view `view_dam_levels`
--
DROP TABLE IF EXISTS `view_dam_levels`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_dam_levels`  AS SELECT `station`.`id` AS `id`, `station`.`code` AS `code`, `station`.`name` AS `name`, `station`.`latitude` AS `latitude`, `station`.`longitude` AS `longitude`, `station`.`category` AS `category`, `dam_levels`.`id` AS `dam_levels_id`, `dam_levels`.`fsc` AS `fsc`, `dam_levels`.`storage` AS `storage`, `dam_levels`.`date` AS `date` FROM (`station` left join `dam_levels` on(`station`.`code` = `dam_levels`.`station_id`)) WHERE `station`.`category` = 'Dam Levels''Dam Levels'  ;

-- --------------------------------------------------------

--
-- Structure for view `view_flow_levels`
--
DROP TABLE IF EXISTS `view_flow_levels`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_flow_levels`  AS SELECT `station`.`id` AS `id`, `station`.`code` AS `code`, `station`.`name` AS `name`, `station`.`latitude` AS `latitude`, `station`.`longitude` AS `longitude`, `station`.`category` AS `category`, `flow_levels`.`id` AS `flow_levels_id`, `flow_levels`.`value` AS `value`, `flow_levels`.`unit` AS `unit`, `flow_levels`.`date` AS `date` FROM (`station` left join `flow_levels` on(`station`.`code` = `flow_levels`.`station_id`)) WHERE `station`.`category` = 'Flow Levels''Flow Levels'  ;

-- --------------------------------------------------------

--
-- Structure for view `view_rainfall`
--
DROP TABLE IF EXISTS `view_rainfall`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_rainfall`  AS SELECT `station`.`id` AS `id`, `station`.`code` AS `code`, `station`.`name` AS `name`, `station`.`latitude` AS `latitude`, `station`.`longitude` AS `longitude`, `station`.`category` AS `category`, `rainfall`.`value` AS `value`, `rainfall`.`unit` AS `unit`, `rainfall`.`date` AS `date` FROM (`station` left join `rainfall` on(`station`.`code` = `rainfall`.`station_id`)) WHERE `station`.`category` = 'Rainfall''Rainfall'  ;

-- --------------------------------------------------------

--
-- Structure for view `view_water_quality`
--
DROP TABLE IF EXISTS `view_water_quality`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_water_quality`  AS SELECT `station`.`id` AS `id`, `station`.`code` AS `code`, `station`.`name` AS `name`, `station`.`latitude` AS `latitude`, `station`.`longitude` AS `longitude`, `station`.`category` AS `category`, `water_quality`.`value` AS `value`, `water_quality`.`unit` AS `unit`, `water_quality`.`date` AS `date` FROM (`station` left join `water_quality` on(`station`.`code` = `water_quality`.`station_id`)) WHERE `station`.`category` = 'Water Quality''Water Quality'  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dam_levels`
--
ALTER TABLE `dam_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `station_id` (`station_id`);

--
-- Indexes for table `flow_levels`
--
ALTER TABLE `flow_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `groundwater`
--
ALTER TABLE `groundwater`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rainfall`
--
ALTER TABLE `rainfall`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `station`
--
ALTER TABLE `station`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `treshhold`
--
ALTER TABLE `treshhold`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `station_id` (`station_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `water_quality`
--
ALTER TABLE `water_quality`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dam_levels`
--
ALTER TABLE `dam_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `flow_levels`
--
ALTER TABLE `flow_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `groundwater`
--
ALTER TABLE `groundwater`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rainfall`
--
ALTER TABLE `rainfall`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `station`
--
ALTER TABLE `station`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=243;

--
-- AUTO_INCREMENT for table `treshhold`
--
ALTER TABLE `treshhold`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `water_quality`
--
ALTER TABLE `water_quality`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
