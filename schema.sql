-- Veritabanı Adı: akdeniz_yemekhane
-- Bu dosya, veritabanı yapısını kurar. `setup.php` tarafından çalıştırılır.

CREATE DATABASE IF NOT EXISTS akdeniz_yemekhane CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE akdeniz_yemekhane;

-- Tabloların en güncel halini oluşturur, eğer yoksa.
CREATE TABLE IF NOT EXISTS `meals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `calories` INT,
  `ingredients` TEXT,
  `is_vegetarian` BOOLEAN DEFAULT FALSE,
  `is_gluten_free` BOOLEAN DEFAULT FALSE,
  `has_allergens` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `menus` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `menu_date` DATE NOT NULL,
  `meal_id` INT NOT NULL,
  UNIQUE KEY `date_meal` (`menu_date`, `meal_id`),
  FOREIGN KEY (`meal_id`) REFERENCES `meals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `special_days` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_date` DATE NOT NULL UNIQUE,
  `message` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_username` VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(45),
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `feedback` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `rating` TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `comment` TEXT,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'yeni', -- 'yeni', 'okundu', 'cevaplandı'
  `is_read` BOOLEAN NOT NULL DEFAULT 0,
  `replied_by` VARCHAR(50) DEFAULT NULL,
  `replied_at` DATETIME DEFAULT NULL,
  `reply_text` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` VARCHAR(50) NOT NULL PRIMARY KEY,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;