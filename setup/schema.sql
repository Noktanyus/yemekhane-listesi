-- Veritabanı Adı: akdeniz_yemekhane
-- Bu dosya, veritabanı yapısını kurar. `setup.php` tarafından çalıştırılır.

CREATE DATABASE IF NOT EXISTS akdeniz_yemekhane CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE akdeniz_yemekhane;

-- Tabloların en güncel halini oluşturur, eğer yoksa.
-- Yemekhane Veritabanı Şeması - v2.0 (Nihai ve Tutarlı Sürüm)
-- Tüm tablolar standart olarak utf8mb4_general_ci collation kullanır.
-- Tüm tablolarda veri takibi için created_at ve updated_at zaman damgaları bulunur.

-- Tabloları oluşturmadan önce, varsa eski sürümleri güvenli bir şekilde kaldır.
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `logs`, `feedback`, `special_days`, `menus`, `site_settings`, `meals`, `meal_prices`, `admins`;
SET FOREIGN_KEY_CHECKS=1;

-- Admin Kullanıcıları Tablosu
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Yemekler Tablosu (Yemek Havuzu)
CREATE TABLE `meals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL UNIQUE,
  `calories` int(11) DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `is_vegetarian` tinyint(1) NOT NULL DEFAULT 0,
  `is_gluten_free` tinyint(1) NOT NULL DEFAULT 0,
  `has_allergens` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Menüler Tablosu (Hangi gün hangi yemek var?)
CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_date` date NOT NULL,
  `meal_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `date_meal_unique` (`menu_date`,`meal_id`),
  CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`meal_id`) REFERENCES `meals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Özel Günler Tablosu (Tatiller vb.)
CREATE TABLE `special_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_date` date NOT NULL UNIQUE,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Geri Bildirim Tablosu
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` tinyint NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `reply_message` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`replied_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Yönetici Logları Tablosu
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_username` varchar(50) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Site Ayarları Tablosu (Yetkililer vb.)
CREATE TABLE `site_settings` (
  `setting_key` varchar(50) NOT NULL PRIMARY KEY,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Yemek Ücretleri Tablosu
CREATE TABLE IF NOT EXISTS `meal_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;