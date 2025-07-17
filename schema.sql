-- Veritabanı Adı: akdeniz_yemekhane
-- Kullanıcı Adı: root
-- Şifre: root

CREATE DATABASE IF NOT EXISTS akdeniz_yemekhane CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE akdeniz_yemekhane;

-- Yemeklerin tutulacağı tablo
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

-- Menülerin tarih ve yemekle eşleştirildiği tablo
CREATE TABLE IF NOT EXISTS `menus` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `menu_date` DATE NOT NULL,
  `meal_id` INT NOT NULL,
  UNIQUE KEY `date_meal` (`menu_date`, `meal_id`),
  FOREIGN KEY (`meal_id`) REFERENCES `meals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tatil veya özel günlerin tutulduğu tablo
CREATE TABLE IF NOT EXISTS `special_days` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_date` DATE NOT NULL UNIQUE,
  `message` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Yönetici bilgilerinin tutulduğu tablo
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Yönetici işlemlerinin kaydedileceği log tablosu
CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_username` VARCHAR(50) NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Örnek Veriler (Eğer admin yoksa eklenir)
INSERT IGNORE INTO `admins` (`username`, `password_hash`) VALUES
('admin', '$2y$10$g.p/g6vT8B5t7j/Z5a.Lz.Lq0n5oH.S3j5L6kF9c8b7a6s5d4e3f');

-- Örnek Veriler (Eğer yemekler yoksa eklenir)
INSERT IGNORE INTO `meals` (`id`, `name`, `calories`, `ingredients`) VALUES
(1, 'Mercimek Çorbası', 150, 'Mercimek, soğan, havuç, patates, salça, yağ, tuz, baharat'),
(2, 'Tavuk Sote', 350, 'Tavuk göğsü, biber, domates, soğan, sarımsak, yağ, baharat'),
(3, 'Pirinç Pilavı', 200, 'Pirinç, tereyağı, şehriye, tuz, su'),
(4, 'Mevsim Salata', 80, 'Marul, domates, salatalık, havuç, mısır, zeytinyağı, limon'),
(5, 'Ezogelin Çorbası', 180, 'Kırmızı mercimek, bulgur, pirinç, nane, salça'),
(6, 'Orman Kebabı', 450, 'Kuşbaşı et, bezelye, havuç, patates, soğan'),
(7, 'Bulgur Pilavı', 180, 'Bulgur, domates, biber, soğan, tereyağı'),
(8, 'Meyve', 100, 'Mevsimine göre elma, portakal, muz vb.');