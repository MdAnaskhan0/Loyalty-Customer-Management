SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `customersinfo` (
  `customerId` int(11) NOT NULL,
  `customerName` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `entryDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `customersinfo`
  ADD PRIMARY KEY (`customerId`);

ALTER TABLE `customersinfo`
  MODIFY `customerId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;

