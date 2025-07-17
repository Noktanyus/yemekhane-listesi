-- Veritabanı Adı: akdeniz_yemekhane
-- Bu dosya, veritabanı yapısını kurar ve günceller.

CREATE DATABASE IF NOT EXISTS akdeniz_yemekhane CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE akdeniz_yemekhane;

-- Tabloların en güncel halini oluşturur, eğer yoksa.
CREATE TABLE IF NOT EXISTS `meals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `calories` INT,
  `ingredients` TEXT,
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
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- MEALS TABLOSUNU GÜNCELLE (Diyet/Alerjen Sütunları)
-- Bu komutlar, sütunlar yoksa ekler. setup.php bu komutların tekrar çalıştırılmasından doğacak hataları görmezden gelir.
ALTER TABLE `meals`
  ADD COLUMN `is_vegetarian` BOOLEAN DEFAULT FALSE AFTER `ingredients`,
  ADD COLUMN `is_gluten_free` BOOLEAN DEFAULT FALSE AFTER `is_vegetarian`,
  ADD COLUMN `has_allergens` BOOLEAN DEFAULT FALSE AFTER `is_gluten_free`;

-- Örnek Yönetici Verisi (Eğer hiç admin yoksa eklenir)
INSERT INTO `admins` (username, password_hash)
SELECT 'admin', '$2y$10$g.p/g6vT8B5t7j/Z5a.Lz.Lq0n5oH.S3j5L6kF9c8b7a6s5d4e3f'
WHERE NOT EXISTS (SELECT 1 FROM `admins`);
