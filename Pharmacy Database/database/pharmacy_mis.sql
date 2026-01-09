-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 02, 2026 at 02:30 PM
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
-- Database: `pharmacy_mis`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `ArchiveEmployeeRecord` (IN `p_id` VARCHAR(50), IN `p_archivedBy` VARCHAR(50), OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Archive failed';
    END;

    START TRANSACTION;

    -- Move to archive
    INSERT INTO archive_records
    SELECT accountId as employeeId, firstName, middleName, lastName, fullName, contact, userRole as role, schedule, birthday, address, NOW(), p_archivedBy
    FROM accounts
    WHERE accountId = p_id;

    -- Remove from active records
    DELETE FROM accounts WHERE accountId = p_id;

    SET p_success = TRUE;
    SET p_message = 'Account record archived successfully';
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ArchiveInventoryItem` (IN `p_id` VARCHAR(50), IN `p_archivedBy` VARCHAR(50), OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Archive failed';
    END;
    
    START TRANSACTION;
    
    -- Move to archive
        INSERT INTO archive_inventory 
        SELECT productId, brand, genericName, category, manufacturedDate, expirationDate, 
          stock, batchNo, price, supplierId, contact, NOW(), p_archivedBy
        FROM inventory
        WHERE productId = p_id;
    
    -- Remove from active inventory
    DELETE FROM inventory WHERE id = p_id;
    
    SET p_success = TRUE;
    SET p_message = 'Item archived successfully';
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ArchiveSupplier` (IN `p_id` VARCHAR(50), IN `p_archivedBy` VARCHAR(50), OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Archive failed';
    END;

    START TRANSACTION;

    -- Move to archive
    INSERT INTO archive_suppliers
    SELECT supplierId, name, contactPerson, phone, email, address, status, NOW(), p_archivedBy
    FROM suppliers
    WHERE supplierId = p_id;

    -- Remove from active suppliers
    DELETE FROM suppliers WHERE supplierId = p_id;

    SET p_success = TRUE;
    SET p_message = 'Supplier archived successfully';
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateSaleTransaction` (IN `p_productId` VARCHAR(50), IN `p_employeeId` VARCHAR(50), IN `p_quantity` INT, IN `p_paymentMethod` VARCHAR(20), OUT `p_transactionId` VARCHAR(50), OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_stock INT;
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_batchNo VARCHAR(50);
    DECLARE v_counter INT;
    DECLARE v_date VARCHAR(8);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Transaction failed';
    END;

    START TRANSACTION;

    -- Check product stock
    SELECT stock, price, batchNo INTO v_stock, v_price, v_batchNo
    FROM inventory
    WHERE productId = p_productId AND isActive = TRUE
    FOR UPDATE;

    IF v_stock IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Product not found';
        ROLLBACK;
    ELSEIF v_stock < p_quantity THEN
        SET p_success = FALSE;
        SET p_message = 'Insufficient stock';
        ROLLBACK;
    ELSE
        -- Generate transaction ID
        CALL GetNextCounter('Trans', v_counter);
        SET v_date = DATE_FORMAT(CURDATE(), '%Y%m%d');
        SET p_transactionId = CONCAT('Trans', v_date, LPAD(v_counter, 4, '0'));

        -- Insert sale
        INSERT INTO sales (salesId, date, productId, employeeId, quantitySold, totalAmount, batchNo, paymentMethod)
        VALUES (p_transactionId, CURDATE(), p_productId, p_employeeId, p_quantity, v_price * p_quantity, v_batchNo, p_paymentMethod);

        -- Update inventory
        UPDATE inventory
        SET stock = stock - p_quantity
        WHERE productId = p_productId;

        SET p_success = TRUE;
        SET p_message = 'Sale completed successfully';
        COMMIT;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetNextCounter` (IN `p_counterType` VARCHAR(50), OUT `p_nextValue` INT)   BEGIN
    DECLARE v_date DATE;
    SET v_date = CURDATE();

    -- Insert or update counter
    INSERT INTO counters (counterType, counterDate, currentValue)
    VALUES (p_counterType, v_date, 1)
    ON DUPLICATE KEY UPDATE currentValue = currentValue + 1;

    -- Get the current value
    SELECT currentValue INTO p_nextValue
    FROM counters
    WHERE counterType = p_counterType AND counterDate = v_date;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ArchiveSupplyDelivery` (IN `p_id` VARCHAR(50), IN `p_archivedBy` VARCHAR(50), OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Archive failed';
    END;

    START TRANSACTION;

    -- Move to archive (create archive table if needed)
    INSERT INTO archive_deliveries
    SELECT supplyId, supplierId, productId, batchNo, quantity, unitPrice, totalAmount,
           deliveryDate, deliveredDate, status, notes, NOW(), p_archivedBy
    FROM supply_deliveries
    WHERE supplyId = p_id;

    -- Remove from active records
    DELETE FROM supply_deliveries WHERE supplyId = p_id;

    SET p_success = TRUE;
    SET p_message = 'Supply delivery archived successfully';
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateInventoryOnDelivery` (IN `p_productId` VARCHAR(50), IN `p_quantity` INT, OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_current_stock INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = 'Inventory update failed';
    END;

    START TRANSACTION;

    -- Get current stock
    SELECT stock INTO v_current_stock
    FROM inventory
    WHERE productId = p_productId AND isActive = TRUE
    FOR UPDATE;

    IF v_current_stock IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Product not found in inventory';
        ROLLBACK;
    ELSE
        -- Update inventory stock
        UPDATE inventory
        SET stock = stock + p_quantity,
            updatedAt = NOW()
        WHERE productId = p_productId;

        SET p_success = TRUE;
        SET p_message = CONCAT('Inventory updated: stock increased by ', p_quantity);
        COMMIT;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `accountId` varchar(50) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `middleName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) NOT NULL,
  `fullName` varchar(255) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT 'Pharmacy Icons/profile icon.png',
  `schedule` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sex` enum('male','female','other') DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`accountId`, `firstName`, `middleName`, `lastName`, `fullName`, `contact`, `email`, `status`, `username`, `password`, `image`, `schedule`, `birthday`, `address`, `sex`, `isActive`, `createdAt`, `updatedAt`) VALUES
('Acc202511020001', 'Ajae', '', 'Antonio', 'Ajae Antonio', '', 'ajae_antonio16@yahoo.com', 'active', 'Ajae', '$2y$10$8ApJa96QmsfhqdcSS2a79uX4t0qsRLo7wWRWxGwB5yRb71ktqkFRq', 'Pharmacy Icons/profile icon.png', NULL, NULL, NULL, NULL, 1, '2025-11-02 05:44:49', '2025-11-14 09:24:20'),
('Acc202511020002', 'Ajae', '', 'Antonio', 'Ajae Antonio', '', 'unilad@gmail.com', 'active', 'Ajae1', '$2y$10$m3M2n3fTGNmPc/RBxT0OO.QolmTg3gq/gPtnxfZzLL8YxMJRj3LSW', 'Pharmacy Icons/profile icon.png', NULL, NULL, NULL, NULL, 1, '2025-11-02 12:40:04', '2025-11-14 09:24:00'),
('Acc202500000000', 'Super', '', 'Admin', 'Super Admin', '', 'ajaeantonio16@gmail.com', 'active', 'Admin', '$2y$10$LsVAAGyBB1yJH6AR8ZWJkO6AYc.H.8uY7k0raV3OPSc1KScUieTbW', 'Pharmacy Icons/profile icon.png', NULL, NULL, NULL, NULL, 1, '2026-01-02 12:52:47', '2026-01-02 13:08:48');

-- --------------------------------------------------------
-- Roles and user role mappings
-- --------------------------------------------------------

-- -----------------------------
-- RBAC Standard Schema Migration
-- -----------------------------

-- Roles table
CREATE TABLE IF NOT EXISTS `roles` (
  `roleId` INT AUTO_INCREMENT PRIMARY KEY,
  `roleName` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissions table
CREATE TABLE IF NOT EXISTS `permissions` (
  `permissionId` INT AUTO_INCREMENT PRIMARY KEY,
  `permissionName` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Role-Permissions mapping table
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `roleId` INT NOT NULL,
  `permissionId` INT NOT NULL,
  PRIMARY KEY (`roleId`, `permissionId`),
  FOREIGN KEY (`roleId`) REFERENCES `roles`(`roleId`) ON DELETE CASCADE,
  FOREIGN KEY (`permissionId`) REFERENCES `permissions`(`permissionId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User-Roles mapping table
CREATE TABLE IF NOT EXISTS `user_roles` (
  `userId` VARCHAR(50) NOT NULL,
  `roleId` INT NOT NULL,
  PRIMARY KEY (`userId`, `roleId`),
  FOREIGN KEY (`roleId`) REFERENCES `roles`(`roleId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default roles
INSERT INTO `roles` (`roleName`, `description`) VALUES
  ('Super Admin', 'Full system access'),
  ('Admin', 'Administrative user'),
  ('Manager', 'Manager with limited admin rights'),
  ('Staff', 'Standard staff permissions')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Default permissions
INSERT INTO `permissions` (`permissionName`, `description`) VALUES
  ('Account management high', 'Full account management'),
  ('Account management low', 'Limited account management'),
  ('Archive', 'Archive records'),
  ('Catalogs', 'Manage catalogs'),
  ('Inventory', 'Manage inventory'),
  ('Logs', 'View logs'),
  ('Suppliers', 'Manage suppliers'),
  ('Create', 'Create records'),
  ('Update', 'Update records'),
  ('Delete', 'Delete records')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Example: Assign all permissions to Super Admin (roleId=1)
INSERT IGNORE INTO `role_permissions` (`roleId`, `permissionId`)
SELECT 1, permissionId FROM permissions;




-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `activityid` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `userId` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` enum('CREATE','READ','UPDATE','DELETE','LOGIN','LOGOUT','ARCHIVE','RESTORE') DEFAULT NULL,
  `module` enum('INVENTORY','SALES','SUPPLIER','ACCOUNTS','PROFILE','AUTH','ARCHIVE','SYSTEM') DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ipAddress` varchar(45) DEFAULT NULL,
  `recordId` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archive_accounts`
--

CREATE TABLE `archive_accounts` (
  `accountId` varchar(50) NOT NULL,
  `firstName` varchar(100) DEFAULT NULL,
  `middleName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL,
  `fullName` varchar(255) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `archivedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `archivedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archive_inventory`
--

CREATE TABLE `archive_inventory` (
  `productId` varchar(50) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `genericName` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `manufacturedDate` date DEFAULT NULL,
  `expirationDate` date DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `batchNo` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `supplierId` varchar(50) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `archivedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `archivedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archive_records`
--

CREATE TABLE `archive_records` (
  `employeeId` varchar(50) NOT NULL,
  `firstName` varchar(100) DEFAULT NULL,
  `middleName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL,
  `fullName` varchar(255) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `schedule` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `archivedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `archivedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archive_sales`
--

CREATE TABLE `archive_sales` (
  `salesId` varchar(50) NOT NULL,
  `date` date DEFAULT NULL,
  `productId` varchar(50) DEFAULT NULL,
  `employeeId` varchar(50) DEFAULT NULL,
  `quantitySold` int(11) DEFAULT NULL,
  `totalAmount` decimal(10,2) DEFAULT NULL,
  `batchNo` varchar(50) DEFAULT NULL,
  `paymentMethod` varchar(20) DEFAULT NULL,
  `archivedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `archivedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archive_suppliers`
--

CREATE TABLE `archive_suppliers` (
  `supplierId` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `contactPerson` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `archivedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `archivedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archive_deliveries`
--

CREATE TABLE `archive_deliveries` (
  `supplyId` varchar(50) NOT NULL,
  `supplierId` varchar(50) DEFAULT NULL,
  `productId` varchar(50) DEFAULT NULL,
  `batchNo` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unitPrice` decimal(10,2) DEFAULT NULL,
  `totalAmount` decimal(10,2) DEFAULT NULL,
  `deliveryDate` date NOT NULL,
  `deliveredDate` date DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `archivedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `archivedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counters`
--

CREATE TABLE `counters` (
  `counterId` int(11) NOT NULL,
  `counterType` varchar(50) NOT NULL,
  `counterDate` date NOT NULL,
  `currentValue` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counters`
--

INSERT INTO `counters` (`counterId`, `counterType`, `counterDate`, `currentValue`) VALUES
(1, 'Emp', '2025-10-28', 1),
(2, 'Prod', '2025-10-28', 1),
(3, 'batch_2025_TylenolParacetamol', '2025-10-28', 1),
(4, 'Prod', '2025-10-29', 1),
(5, 'batch_2025_BiogesicParacetamol', '2025-10-29', 1),
(6, 'Prod', '2025-10-31', 1),
(7, 'batch_2025_TylenolAcetaminophen', '2025-10-31', 1),
(9, 'Acc', '2025-11-02', 1),
(10, 'Rec', '2025-11-02', 1),
(18, 'Emp', '2025-11-03', 1),
(19, 'Prod', '2025-11-07', 1),
(20, 'batch_2025_BiogesicParacetamol', '2025-11-07', 1);

-- --------------------------------------------------------

--
-- Table structure for table `employee_token`
--

CREATE TABLE `employee_token` (
  `selector` varchar(64) NOT NULL,
  `employeeId` varchar(50) NOT NULL,
  `validator` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL DEFAULT (current_timestamp() + interval 30 day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `productId` varchar(50) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `genericName` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `manufacturedDate` date NOT NULL,
  `expirationDate` date NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `batchNo` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `supplierId` varchar(50) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `requiresPrescription` tinyint(1) DEFAULT 0,
  `lowStockThreshold` int(11) NOT NULL,
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD UNIQUE KEY `unique_batchNo` (`batchNo`);

--
-- Dumping data for table `inventory`
--

-- sample inventory rows (supplierId values should reference `suppliers.supplierId`)
INSERT INTO `inventory` (`productId`, `brand`, `genericName`, `category`, `manufacturedDate`, `expirationDate`, `stock`, `batchNo`, `price`, `supplierId`, `contact`, `requiresPrescription`, `lowStockThreshold`, `isActive`, `createdAt`, `updatedAt`) VALUES
('Prod202511070001', 'Biogesic', 'Paracetamol', 'Pain Killer', '2025-11-01', '2026-01-01', 100, '20250001', 10.00, 'SUP000000', '0947626751234', 0, 10, 1, '2025-11-07 11:58:54', '2025-11-07 11:58:54'),
('Prod202511070002', 'Biogesic', 'Paracetamol', 'Pain Killer', '2025-11-01', '2025-12-31', 5, '20250002', 10.00, 'SUP000000', '0947626751234', 0, 15, 1, '2025-11-07 11:59:28', '2025-11-21 01:17:03'),
('Prod202511070003', 'Biogesic', 'Paracetamol', 'Pain Killer', '2025-11-01', '2026-08-14', 51, '20250003', 110.00, 'SUP000000', '0947626751234', 0, 20, 1, '2025-11-07 11:59:48', '2025-11-07 11:59:48');

--
-- Triggers `inventory`
--
DELIMITER $$
CREATE TRIGGER `trg_inventory_audit_insert` AFTER INSERT ON `inventory` FOR EACH ROW BEGIN
  INSERT INTO activity_logs (userId, username, action, module, details, ipAddress, recordId)
  VALUES (NEW.productId, 'SYSTEM', 'CREATE', 'INVENTORY',
          CONCAT('Created product: ', NEW.brand, ' - ', NEW.genericName), 'SYSTEM', NEW.productId);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_inventory_audit_update` AFTER UPDATE ON `inventory` FOR EACH ROW BEGIN
  INSERT INTO activity_logs (userId, username, action, module, details, ipAddress, recordId)
  VALUES (NEW.productId, 'SYSTEM', 'UPDATE', 'INVENTORY',
          CONCAT('Updated product: ', NEW.brand, ' (Stock: ', OLD.stock, ' → ', NEW.stock, ')'), 'SYSTEM', NEW.productId);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `online_users`
--

CREATE TABLE `online_users` (
  `accountId` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `loginTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastActivity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ipAddress` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `online_users`
--

INSERT INTO `online_users` (`accountId`, `username`, `loginTime`, `lastActivity`, `ipAddress`) VALUES
('Acc202500000000', 'Admin', '2026-01-02 13:08:57', '2026-01-02 13:08:57', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `otp_request`
--

CREATE TABLE `otp_request` (
  `otpId` varchar(50) NOT NULL,
  `accountId` varchar(50) NOT NULL,
  `otpHash` varchar(255) NOT NULL,
  `expiredAt` datetime NOT NULL,
  `isUsed` tinyint(1) DEFAULT 0,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_request`
--

INSERT INTO `otp_request` (`otpId`, `accountId`, `otpHash`, `expiredAt`, `isUsed`, `createdAt`) VALUES
('OTP202601022107497953', 'Acc202500000000', '$2y$10$/4ibWujAPHGpuhPucrOyv.Z4pHiJGM6UW78zJieiaRL3R1in.XKyW', '2026-01-02 21:12:49', 1, '2026-01-02 13:07:49');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `salesId` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `productId` varchar(50) NOT NULL,
  `employeeId` varchar(50) NOT NULL,
  `quantitySold` int(11) NOT NULL,
  `totalAmount` decimal(10,2) NOT NULL,
  `batchNo` varchar(50) DEFAULT NULL,
  `paymentMethod` enum('Cash','Card','E-Wallet') DEFAULT 'Cash',
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `sales`
--
DELIMITER $$
CREATE TRIGGER `trg_sales_audit_insert` AFTER INSERT ON `sales` FOR EACH ROW BEGIN
    INSERT INTO activity_logs (userId, username, action, module, details, ipAddress, recordId)
    VALUES (NEW.employeeId, 'SYSTEM', 'CREATE', 'SALES',
            CONCAT('Sale: ', NEW.productId, ' Qty: ', NEW.quantitySold, ' Amount: ₱', NEW.totalAmount), 'SYSTEM', NEW.salesId);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplierId` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contactPerson` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure a default supplier exists for sample data imports
INSERT INTO `suppliers` (`supplierId`, `name`, `contactPerson`, `phone`, `email`, `address`, `status`, `isActive`, `createdAt`, `updatedAt`) VALUES
('SUP000000', 'Unknown Supplier', 'Unknown', '', '', 'Imported default supplier', 'active', 1, NOW(), NOW());

-- --------------------------------------------------------

--
-- Table structure for table `supply_deliveries`
--

CREATE TABLE `supply_deliveries` (
  `supplyId` varchar(50) NOT NULL,
  `supplierId` varchar(50) NOT NULL,
  `productId` varchar(50) NOT NULL,
  `batchNo` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unitPrice` decimal(10,2) NOT NULL,
  `totalAmount` decimal(10,2) NOT NULL,
  `deliveryDate` date NOT NULL,
  `deliveredDate` date DEFAULT NULL,
  `status` enum('Pending','Received','Cancelled') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- triggers `supply_deliveries`
--
DELIMITER $$
CREATE TRIGGER `trg_supply_deliveries_audit_insert` AFTER INSERT ON `supply_deliveries` FOR EACH ROW BEGIN
    INSERT INTO activity_logs (userId, username, action, module, details, ipAddress, recordId)
    VALUES (NEW.supplyId, 'SYSTEM', 'CREATE', 'SUPPLIERS',
            CONCAT('Delivery created: ', NEW.productId, ' from ', NEW.supplierId, ' Qty: ', NEW.quantity, ' Status: ', NEW.status), 'SYSTEM', NEW.supplyId);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_supply_deliveries_audit_update` AFTER UPDATE ON `supply_deliveries` FOR EACH ROW BEGIN
    INSERT INTO activity_logs (userId, username, action, module, details, ipAddress, recordId)
    VALUES (NEW.supplyId, 'SYSTEM', 'UPDATE', 'SUPPLIERS',
            CONCAT('Delivery updated: ', NEW.productId, ' Status: ', OLD.status, ' → ', NEW.status), 'SYSTEM', NEW.supplyId);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `settingKey` varchar(100) NOT NULL,
  `settingValue` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updatedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `settingKey`, `settingValue`, `description`, `updatedAt`, `updatedBy`) VALUES
(2, 'expiry_alert_days', '100', 'Days before expiry to show alerts', '2025-10-28 14:37:33', NULL),
(3, 'pharmacy_name', 'Echinacea Pharmacy', 'Pharmacy business name', '2025-10-28 14:37:33', NULL),
(4, 'pharmacy_address', '', 'Pharmacy business address', '2025-10-28 14:37:33', NULL),
(5, 'pharmacy_contact', '', 'Pharmacy contact number', '2025-10-28 14:37:33', NULL),
(6, 'pharmacy_email', 'EchinaceaPharmacy@gmail.com', 'Pharmacy email address', '2025-10-28 14:37:33', NULL),
(7, 'tax_rate', '0.12', 'VAT/Tax rate (Philippine 12% VAT)', '2025-10-28 14:37:33', NULL),
(8, 'receipt_footer', 'Thank you for your purchase!', 'Receipt footer message', '2025-10-28 14:37:33', NULL);


-- --------------------------------------------------------

--
-- Stand-in structure for view `view_expiring_soon`
-- (See below for the actual view)
--
CREATE TABLE `view_expiring_soon` (
`id` varchar(50)
,`brand` varchar(255)
,`genericName` varchar(255)
,`category` varchar(100)
,`stock` int(11)
,`expirationDate` date
,`daysToExpiry` int(7)
,`supplier` varchar(255)
,`contact` varchar(20)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_inventory_value`
-- (See below for the actual view)
--
CREATE TABLE `view_inventory_value` (
`category` varchar(100)
,`productCount` bigint(21)
,`totalStock` decimal(32,0)
,`totalValue` decimal(42,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_low_stock`
-- (See below for the actual view)
--
CREATE TABLE `view_low_stock` (
`id` varchar(50)
,`brand` varchar(255)
,`genericName` varchar(255)
,`category` varchar(100)
,`stock` int(11)
,`price` decimal(10,2)
,`supplier` varchar(255)
,`contact` varchar(20)
,`batchNo` varchar(50)
,`expirationDate` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_sales_details`
-- (See below for the actual view)
--
CREATE TABLE `view_sales_details` (
`transactionId` varchar(50)
,`date` date
,`productId` varchar(50)
,`brand` varchar(255)
,`genericName` varchar(255)
,`batchNo` varchar(50)
,`employeeId` varchar(50)
,`employeeName` varchar(201)
,`quantitySold` int(11)
,`unitPrice` decimal(10,2)
,`totalAmount` decimal(10,2)
,`paymentMethod` enum('Cash','Card','E-Wallet')
,`createdAt` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_sales_today`
-- (See below for the actual view)
--
CREATE TABLE `view_sales_today` (
`totalTransactions` bigint(21)
,`totalItemsSold` decimal(32,0)
,`totalRevenue` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Structure for view `view_expiring_soon`
--
DROP TABLE IF EXISTS `view_expiring_soon`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_expiring_soon`  AS 
SELECT i.`productId` AS `id`, i.`brand` AS `brand`, i.`genericName` AS `genericName`, i.`category` AS `category`, i.`stock` AS `stock`, i.`expirationDate` AS `expirationDate`, TO_DAYS(i.`expirationDate`) - TO_DAYS(CURDATE()) AS `daysToExpiry`, s.`name` AS `supplier`, i.`contact` AS `contact`
FROM `inventory` i
LEFT JOIN `suppliers` s ON i.supplierId = s.supplierId
WHERE TO_DAYS(i.`expirationDate`) - TO_DAYS(CURDATE()) <= (SELECT `system_settings`.`settingValue` FROM `system_settings` WHERE `system_settings`.`settingKey` = 'expiry_alert_days') AND TO_DAYS(i.`expirationDate`) - TO_DAYS(CURDATE()) >= 0 AND i.`isActive` = 1
ORDER BY i.`expirationDate` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `view_inventory_value`
--
DROP TABLE IF EXISTS `view_inventory_value`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_inventory_value`  AS SELECT `inventory`.`category` AS `category`, count(0) AS `productCount`, sum(`inventory`.`stock`) AS `totalStock`, sum(`inventory`.`stock` * `inventory`.`price`) AS `totalValue` FROM `inventory` WHERE `inventory`.`isActive` = 1 GROUP BY `inventory`.`category` ;

-- --------------------------------------------------------

--
-- Structure for view `view_low_stock`
--
DROP TABLE IF EXISTS `view_low_stock`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_low_stock`  AS 
SELECT i.`productId` AS `id`, i.`brand` AS `brand`, i.`genericName` AS `genericName`, i.`category` AS `category`, i.`stock` AS `stock`, i.`price` AS `price`, s.`name` AS `supplier`, i.`contact` AS `contact`, i.`batchNo` AS `batchNo`, i.`expirationDate` AS `expirationDate`
FROM `inventory` i
LEFT JOIN `suppliers` s ON i.supplierId = s.supplierId
WHERE i.`stock` < i.`lowStockThreshold` AND i.`isActive` = 1
ORDER BY i.`stock` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `view_sales_details`
--
DROP TABLE IF EXISTS `view_sales_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_sales_details`  AS SELECT `s`.`salesId` AS `transactionId`, `s`.`date` AS `date`, `s`.`productId` AS `productId`, `i`.`brand` AS `brand`, `i`.`genericName` AS `genericName`, `s`.`batchNo` AS `batchNo`, `s`.`employeeId` AS `employeeId`, concat(`a`.`firstName`,' ',`a`.`lastName`) AS `employeeName`, `s`.`quantitySold` AS `quantitySold`, `i`.`price` AS `unitPrice`, `s`.`totalAmount` AS `totalAmount`, `s`.`paymentMethod` AS `paymentMethod`, `s`.`createdAt` AS `createdAt` FROM ((`sales` `s` left join `inventory` `i` on(`s`.`productId` = `i`.`productId`)) left join `accounts` `a` on(`s`.`employeeId` = `a`.`accountId`)) ORDER BY `s`.`createdAt` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `view_sales_today`
--
DROP TABLE IF EXISTS `view_sales_today`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_sales_today`  AS SELECT count(0) AS `totalTransactions`, sum(`sales`.`quantitySold`) AS `totalItemsSold`, sum(`sales`.`totalAmount`) AS `totalRevenue` FROM `sales` WHERE `sales`.`date` = curdate() ;

--
-- Stand-in structure for view `view_expired`
-- (See below for the actual view)
--
CREATE TABLE `view_expired` (
`id` varchar(50)
,`brand` varchar(255)
,`genericName` varchar(255)
,`category` varchar(100)
,`stock` int(11)
,`expirationDate` date
,`supplier` varchar(255)
,`contact` varchar(20)
);

--
-- Stand-in structure for view `view_no_stock`
-- (See below for the actual view)
--
CREATE TABLE `view_no_stock` (
`id` varchar(50)
,`brand` varchar(255)
,`genericName` varchar(255)
,`category` varchar(100)
,`price` decimal(10,2)
,`supplier` varchar(255)
,`contact` varchar(20)
,`batchNo` varchar(50)
,`expirationDate` date
);

--
-- Structure for view `view_expired`
--
DROP TABLE IF EXISTS `view_expired`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_expired`  AS 
SELECT i.`productId` AS `id`, i.`brand` AS `brand`, i.`genericName` AS `genericName`, i.`category` AS `category`, i.`stock` AS `stock`, i.`expirationDate` AS `expirationDate`, s.`name` AS `supplier`, i.`contact` AS `contact`
FROM `inventory` i
LEFT JOIN `suppliers` s ON i.supplierId = s.supplierId
WHERE i.`expirationDate` < CURDATE() AND i.`isActive` = 1
ORDER BY i.`expirationDate` ASC ;

--
-- Structure for view `view_no_stock`
--
DROP TABLE IF EXISTS `view_no_stock`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_no_stock`  AS 
SELECT i.`productId` AS `id`, i.`brand` AS `brand`, i.`genericName` AS `genericName`, i.`category` AS `category`, i.`price` AS `price`, s.`name` AS `supplier`, i.`contact` AS `contact`, i.`batchNo` AS `batchNo`, i.`expirationDate` AS `expirationDate`
FROM `inventory` i
LEFT JOIN `suppliers` s ON i.supplierId = s.supplierId
WHERE i.`stock` = 0 AND i.`isActive` = 1
ORDER BY i.`brand` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`accountId`) USING BTREE,
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`activityid`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_user` (`username`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_module` (`module`);

--
-- Indexes for table `archive_accounts`
--
ALTER TABLE `archive_accounts`
  ADD PRIMARY KEY (`accountId`);

--
-- Indexes for table `archive_inventory`
--
ALTER TABLE `archive_inventory`
  ADD PRIMARY KEY (`productId`) USING BTREE;

--
-- Indexes for table `archive_records`
--
ALTER TABLE `archive_records`
  ADD PRIMARY KEY (`employeeId`) USING BTREE;

--
-- Indexes for table `archive_sales`
--
ALTER TABLE `archive_sales`
  ADD PRIMARY KEY (`salesId`);

--
-- Indexes for table `archive_suppliers`
--
ALTER TABLE `archive_suppliers`
  ADD PRIMARY KEY (`supplierId`);

--
-- Indexes for table `archive_deliveries`
--
ALTER TABLE `archive_deliveries`
  ADD PRIMARY KEY (`supplyId`);

--
-- Indexes for table `counters`
--
ALTER TABLE `counters`
  ADD PRIMARY KEY (`counterId`) USING BTREE,
  ADD UNIQUE KEY `unique_counter` (`counterType`,`counterDate`),
  ADD KEY `idx_type_date` (`counterType`,`counterDate`);

--
-- Indexes for table `employee_token`
--
ALTER TABLE `employee_token`
  ADD KEY `employee_token_ibfk_1` (`employeeId`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`productId`) USING BTREE,
  ADD KEY `idx_brand` (`brand`),
  ADD KEY `idx_generic` (`genericName`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_expiry` (`expirationDate`),
  ADD KEY `idx_stock` (`stock`),
  ADD KEY `idx_inventory_active_stock` (`isActive`,`stock`),
  ADD KEY `idx_inventory_active_expiry` (`isActive`,`expirationDate`);

--
-- Indexes for table `online_users`
--
ALTER TABLE `online_users`
  ADD PRIMARY KEY (`accountId`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `otp_request`
--
ALTER TABLE `otp_request`
  ADD KEY `otp_request_ibfk_1` (`accountId`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`salesId`) USING BTREE,
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_product` (`productId`),
  ADD KEY `idx_employee` (`employeeId`),
  ADD KEY `idx_sales_date_product` (`date`,`productId`),
  ADD KEY `idx_sales_date_employee` (`date`,`employeeId`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplierId`) USING BTREE,
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settingKey` (`settingKey`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `activityid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=224;

--
-- AUTO_INCREMENT for table `counters`
--
ALTER TABLE `counters`
  MODIFY `counterId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;


--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--


--
-- Constraints for table `employee_token`
--
ALTER TABLE `employee_token`
  ADD CONSTRAINT `employee_token_ibfk_1` FOREIGN KEY (`employeeId`) REFERENCES `accounts` (`accountId`) ON DELETE CASCADE;

--
-- Constraints for table `online_users`
--
ALTER TABLE `online_users`
  ADD CONSTRAINT `online_users_ibfk_1` FOREIGN KEY (`username`) REFERENCES `accounts` (`username`) ON DELETE CASCADE,
  ADD CONSTRAINT `online_users_ibfk_2` FOREIGN KEY (`accountId`) REFERENCES `accounts` (`accountId`) ON DELETE CASCADE;

--
-- Constraints for table `otp_request`
--
ALTER TABLE `otp_request`
  ADD CONSTRAINT `otp_request_ibfk_1` FOREIGN KEY (`accountId`) REFERENCES `accounts` (`accountId`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`productId`) REFERENCES `inventory` (`productId`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`employeeId`) REFERENCES `accounts` (`accountId`);

--
-- Indexes for table `supply_deliveries`
--
ALTER TABLE `supply_deliveries`
  ADD PRIMARY KEY (`supplyId`),
  ADD KEY `idx_supplier_delivery` (`supplierId`),
  ADD KEY `idx_product_delivery` (`productId`),
  ADD KEY `idx_delivery_date` (`deliveredDate`),
  ADD KEY `idx_delivery_status` (`status`),
  ADD KEY `idx_supply_deliveries_active` (`isActive`);

--
-- Constraints for table `supply_deliveries`
--
ALTER TABLE `supply_deliveries`
  ADD CONSTRAINT `supply_deliveries_ibfk_1` FOREIGN KEY (`supplierId`) REFERENCES `suppliers` (`supplierId`),
  ADD CONSTRAINT `supply_deliveries_ibfk_2` FOREIGN KEY (`productId`) REFERENCES `inventory` (`productId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Assign Super Admin to Super Admin role
INSERT IGNORE INTO user_roles (userId, roleId) VALUES ('Acc202500000000', 1);