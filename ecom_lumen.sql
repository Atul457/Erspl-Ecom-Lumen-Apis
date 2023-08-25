-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2023 at 10:21 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecom_lumen`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`atul`@`localhost` PROCEDURE `check_balance` (IN `userId` INT, IN `orderTotal` DECIMAL(10,2))   BEGIN
            DECLARE userId_ INT;
            DECLARE userNotFound BOOLEAN;
            DECLARE walletBalance DECIMAL(10, 2);
            DECLARE requiredBalance DECIMAL(10, 2);
            DECLARE isInsuffecientBalance BOOLEAN;
        
            SELECT COALESCE(wallet_balance, 0), id INTO walletBalance, userId_ FROM users WHERE id = userId;
        
            IF userId_ IS NULL THEN
                SET userNotFound = TRUE;
                SET walletBalance = 0.00;
                SET requiredBalance = 0.00;
                SET isInsuffecientBalance = FALSE;
            ELSE
                SET userNotFound = FALSE;

                IF walletBalance IS NULL THEN
                    SET walletBalance = 0;
                END IF;

                SET requiredBalance = orderTotal - walletBalance;
                    
                IF requiredBalance > 0 THEN
                    SET isInsuffecientBalance = TRUE;
                ELSE
                    SET isInsuffecientBalance = FALSE;
                END IF;

            END IF;
        
            SELECT isInsuffecientBalance AS is_insufficient_balance, requiredBalance AS required_balance, walletBalance AS wallet_balance, userNotFound AS user_not_found;
        END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `address_book`
--

CREATE TABLE `address_book` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `flat` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `state` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `mobile` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `pincode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `country` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `latitude` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `landmark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `longitude` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `address` longtext CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `default_status` tinyint(4) NOT NULL DEFAULT 0,
  `address_type` int(11) DEFAULT NULL COMMENT '1 = home, 2 = work, 3 = other',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `address_book`
--

INSERT INTO `address_book` (`id`, `name`, `city`, `flat`, `state`, `mobile`, `pincode`, `country`, `latitude`, `landmark`, `longitude`, `address`, `customer_id`, `default_status`, `address_type`, `created_at`, `updated_at`) VALUES
(1, 'Atul', 'Chandigarh', '8521', 'Chandigarh', '8837684275', '160102', 'India', '263', 'near Khera mandir', '56.36', 'Mauli jagran', 6, 1, NULL, '2023-08-25 20:14:54', '2023-08-25 20:17:42'),
(2, NULL, NULL, NULL, 'Delhi metro', '8837684275', NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL, '2023-08-25 20:16:04', '2023-08-25 20:17:42'),
(3, NULL, NULL, NULL, 'Delhi metro', '8837684275', NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL, '2023-08-25 20:18:03', '2023-08-25 20:18:03');

-- --------------------------------------------------------

--
-- Table structure for table `home`
--

CREATE TABLE `home` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `gst` double(8,2) NOT NULL,
  `logo` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `title2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `title1` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `version` double(8,2) NOT NULL,
  `contact` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `zap_key` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `count2` int(11) NOT NULL,
  `count1` int(11) NOT NULL,
  `fav_icon` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `gallery1` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `gallery2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `address` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `paytm_mid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `paytm_mkey` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `category2` int(11) NOT NULL,
  `tInfo_temp` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `sms_vendor` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `shop_range` int(11) NOT NULL,
  `fortius_key` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `company_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `meta_keyword` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `category1_id` int(11) NOT NULL,
  `category2_id` int(11) NOT NULL,
  `packing_charge` double(8,2) NOT NULL,
  `shop_capping` int(11) NOT NULL,
  `force_status` int(11) NOT NULL,
  `wallet_status` int(11) NOT NULL,
  `referral_amount` double(8,2) NOT NULL,
  `minimum_value` int(11) NOT NULL,
  `delivery_charge` double(8,2) NOT NULL,
  `order_capping` int(11) NOT NULL,
  `prepaid_status` int(11) NOT NULL,
  `weight_capping` int(11) NOT NULL,
  `meta_description` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `notification_key` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `footer_description` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `delivery_type` int(11) NOT NULL COMMENT '1=Free, 2=Paid',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `home`
--

INSERT INTO `home` (`id`, `gst`, `logo`, `email`, `title`, `title2`, `title1`, `version`, `contact`, `zap_key`, `count2`, `count1`, `fav_icon`, `gallery1`, `gallery2`, `address`, `paytm_mid`, `paytm_mkey`, `category2`, `tInfo_temp`, `sms_vendor`, `shop_range`, `fortius_key`, `company_name`, `meta_keyword`, `category1_id`, `category2_id`, `packing_charge`, `shop_capping`, `force_status`, `wallet_status`, `referral_amount`, `minimum_value`, `delivery_charge`, `order_capping`, `prepaid_status`, `weight_capping`, `meta_description`, `notification_key`, `footer_description`, `delivery_type`, `created_at`, `updated_at`) VALUES
(18, 18.00, 'logo.png', 'e-rspl@rsplgroup.com', 'eRSPL', 'Household Care', 'Top Products', 3.00, '18002570369', 'https://api.zapsms.co.in/api/v2/SendSMS?SenderId=eRSPLe&Is_Unicode=false&Is_Flash=false&Message=[MESSAGE]&MobileNumbers=[MOBILE_NUMBER]=lsdzpI6f%2BipF%2BwG1j4iwQ%2FjIQS9PS4VC0uftsQih4hY%3D&ClientId=c814d93d-a836-4f39-8b47-6798657c8072', 8, 8, 'Logo-01.png', 'bannerNewE.jpg', 'bannerNewD.jpg', 'RSPL Limited, Plot No 124, Sec 44, Gurgaon', 'RSPLLI18958753559585', 'yaI9&%X17oEGd5RI', 5, 'bhan', 'ZAP', 8, 'http://smsfortius.in/api/mt/SendSMS?user=nifood&password=nifood629&senderid=eRSPLe&channel=Trans&DCS=0&flashsms=0&number=[MOBILE_NUMBER]&text=[MESSAGE]&route=02', 'Kirana Store Pvt. Ltd.', 'Kirana Store', 5, 5, 0.00, 10, 1, 0, 60.00, 1, 100.00, 10, 1, 25000, 'Kirana Store', 'AAAAShMLOyc:APA91bGHffxM0MHzsG6sdhx27jnsaH5ixc50e082UMI-c9y6oc3VQjtMPIWuLbxNLEr0c88Qm_B7rV0L84EHszEduxVFprGCevwHmPnaVCbr5iJsrkIESjvT-SA2NNcDd8ejFBfA3exP', 'Vishwas Mart in Jaipur is one of the leading businesses in the General Stores. Also known for General Stores, Departmental Stores, Supermarkets, Provision Stores, Bakery Product Retailers, Grocery Home Delivery Services, Milk Home Delivery Services, Bakery Food Home Delivery and much more. Find Address, Contact Number, Reviews & Ratings, Photos, Maps of Vishwas Mart, Jaipur.', 1, '2023-08-25 19:15:24', '2023-08-25 19:15:24'),
(19, 18.00, 'logo.png', 'e-rspl@rsplgroup.com', 'eRSPL', 'Household Care', 'Top Products', 3.00, '18002570369', 'https://api.zapsms.co.in/api/v2/SendSMS?SenderId=eRSPLe&Is_Unicode=false&Is_Flash=false&Message=[MESSAGE]&MobileNumbers=[MOBILE_NUMBER]=lsdzpI6f%2BipF%2BwG1j4iwQ%2FjIQS9PS4VC0uftsQih4hY%3D&ClientId=c814d93d-a836-4f39-8b47-6798657c8072', 8, 8, 'Logo-01.png', 'bannerNewE.jpg', 'bannerNewD.jpg', 'RSPL Limited, Plot No 124, Sec 44, Gurgaon', 'RSPLLI18958753559585', 'yaI9&%X17oEGd5RI', 5, 'bhan', 'ZAP', 8, 'http://smsfortius.in/api/mt/SendSMS?user=nifood&password=nifood629&senderid=eRSPLe&channel=Trans&DCS=0&flashsms=0&number=[MOBILE_NUMBER]&text=[MESSAGE]&route=02', 'Kirana Store Pvt. Ltd.', 'Kirana Store', 5, 5, 0.00, 10, 1, 0, 60.00, 1, 100.00, 10, 1, 25000, 'Kirana Store', 'AAAAShMLOyc:APA91bGHffxM0MHzsG6sdhx27jnsaH5ixc50e082UMI-c9y6oc3VQjtMPIWuLbxNLEr0c88Qm_B7rV0L84EHszEduxVFprGCevwHmPnaVCbr5iJsrkIESjvT-SA2NNcDd8ejFBfA3exP', 'Vishwas Mart in Jaipur is one of the leading businesses in the General Stores. Also known for General Stores, Departmental Stores, Supermarkets, Provision Stores, Bakery Product Retailers, Grocery Home Delivery Services, Milk Home Delivery Services, Bakery Food Home Delivery and much more. Find Address, Contact Number, Reviews & Ratings, Photos, Maps of Vishwas Mart, Jaipur.', 1, '2023-08-25 19:17:01', '2023-08-25 19:17:01');

-- --------------------------------------------------------

--
-- Table structure for table `industries`
--

CREATE TABLE `industries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `category_order` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `catlog` varchar(255) DEFAULT NULL,
  `slug_url` varchar(255) NOT NULL DEFAULT '',
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `offer_title` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords` varchar(255) NOT NULL DEFAULT '',
  `meta_description` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(15, '2014_10_12_000000_create_users_table', 1),
(16, '2023_08_19_112956_create_home_table', 1),
(17, '2023_08_20_205655_create_address_book_table', 1),
(18, '2023_08_22_152832_create_users_temps_table', 1),
(19, '2023_08_23_150949_create_wallet_procedures', 1),
(20, '2023_08_23_183748_create_industries_table', 1),
(21, '2023_08_23_184058_create_wallet_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `otp` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reg_type` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `attempt` int(11) NOT NULL DEFAULT 0,
  `otp_datetime` date DEFAULT NULL,
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `alt_mobile` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `referral_by` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) NOT NULL DEFAULT '',
  `guest_status` int(11) NOT NULL DEFAULT 0,
  `referral_code` varchar(255) DEFAULT NULL,
  `wallet_balance` double(8,2) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `suspended_datetime` date DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1 COMMENT '0 = Inactive, 1 = Active',
  `gender` tinyint(4) DEFAULT NULL COMMENT '0 = Male, 1 = Female, 2 = Other',
  `email_status` int(11) NOT NULL DEFAULT 0 COMMENT '0 = Verification pending, 1 = Email verified',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `otp`, `mobile`, `password`, `reg_type`, `dob`, `email`, `image`, `attempt`, `otp_datetime`, `last_name`, `alt_mobile`, `first_name`, `referral_by`, `middle_name`, `guest_status`, `referral_code`, `wallet_balance`, `token`, `suspended_datetime`, `email_verified_at`, `status`, `gender`, `email_status`, `remember_token`, `created_at`, `updated_at`) VALUES
(6, '0000', '9779755869', '$2y$10$GVMvG.KDfRLLhLpKA635yeUeDf3xh.mQzMizB3FOSjpzvgo3.67dW', 'App', '2000-10-01', 'anmol@mailinator.com', NULL, 1, NULL, '', NULL, 'anmol', NULL, '', 0, '9779755869ERSPL', NULL, NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, '2023-08-25 20:09:48');

-- --------------------------------------------------------

--
-- Table structure for table `users_temp`
--

CREATE TABLE `users_temp` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `otp` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `reg_type` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `attempt` int(11) NOT NULL DEFAULT 0,
  `otp_datetime` date DEFAULT NULL,
  `last_name` varchar(255) NOT NULL DEFAULT '',
  `alt_mobile` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL DEFAULT '',
  `referral_by` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) NOT NULL DEFAULT '',
  `guest_status` int(11) NOT NULL DEFAULT 0,
  `referral_code` varchar(255) DEFAULT NULL,
  `wallet_balance` int(11) DEFAULT NULL,
  `suspended_datetime` date DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1 COMMENT '0 = Inactive, 1 = Active',
  `gender` tinyint(4) DEFAULT NULL COMMENT '0 = Male, 1 = Female, 2 = Other',
  `email_status` int(11) NOT NULL DEFAULT 0 COMMENT '0 = Verification pending, 1 = Email verified',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_temp`
--

INSERT INTO `users_temp` (`id`, `otp`, `mobile`, `password`, `reg_type`, `dob`, `email`, `image`, `attempt`, `otp_datetime`, `last_name`, `alt_mobile`, `first_name`, `referral_by`, `middle_name`, `guest_status`, `referral_code`, `wallet_balance`, `suspended_datetime`, `status`, `gender`, `email_status`, `created_at`, `updated_at`) VALUES
(9, '0000', '9779755869', '$2y$10$GVMvG.KDfRLLhLpKA635yeUeDf3xh.mQzMizB3FOSjpzvgo3.67dW', 'App', '2000-10-01', 'anmol@mailinator.com', NULL, 1, NULL, '', NULL, 'anmol', NULL, '', 0, '9779755869ERSPL', NULL, NULL, 1, NULL, 0, NULL, '2023-08-25 20:08:32');

-- --------------------------------------------------------

--
-- Table structure for table `wallet`
--

CREATE TABLE `wallet` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `category_code` varchar(255) NOT NULL,
  `category_order` int(11) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `catlog` varchar(255) DEFAULT NULL,
  `slider` varchar(255) NOT NULL DEFAULT '',
  `slug_url` varchar(255) NOT NULL DEFAULT '',
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `offer_title` varchar(255) DEFAULT NULL,
  `industries_id` bigint(20) UNSIGNED NOT NULL,
  `meta_keywords` varchar(255) NOT NULL DEFAULT '',
  `meta_description` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address_book`
--
ALTER TABLE `address_book`
  ADD PRIMARY KEY (`id`),
  ADD KEY `address_book_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `home`
--
ALTER TABLE `home`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `industries`
--
ALTER TABLE `industries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_token_unique` (`token`);

--
-- Indexes for table `users_temp`
--
ALTER TABLE `users_temp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_temp_email_unique` (`email`);

--
-- Indexes for table `wallet`
--
ALTER TABLE `wallet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wallet_industries_id_foreign` (`industries_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address_book`
--
ALTER TABLE `address_book`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `home`
--
ALTER TABLE `home`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `industries`
--
ALTER TABLE `industries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users_temp`
--
ALTER TABLE `users_temp`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wallet`
--
ALTER TABLE `wallet`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address_book`
--
ALTER TABLE `address_book`
  ADD CONSTRAINT `address_book_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `wallet`
--
ALTER TABLE `wallet`
  ADD CONSTRAINT `wallet_industries_id_foreign` FOREIGN KEY (`industries_id`) REFERENCES `industries` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
