<?php

// config.php

// Hata raporlamayı geliştirme aşamasında açın, produksiyonda kapatın.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);


define('DB_HOST', 'localhost');
define('DB_NAME', 'akdeniz_yemekhane');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Cloudflare Turnstile Test Anahtarları
// Bu anahtarlar her zaman doğrulamayı başarılı kılar.
// Gerçek kullanım için Cloudflare panelinizden kendi anahtarlarınızı almalısınız.
define('CLOUDFLARE_SITE_KEY', '1x00000000000000000000AA');
define('CLOUDFLARE_SECRET_KEY', '1x0000000000000000000000000000000AA');

// Diğer uygulama ayarları
define('APP_URL', 'http://localhost/');
define('APP_NAME', 'Akdeniz Üniversitesi Yemekhane Menüsü');

// --- SMTP Ayarları (PHPMailer için) ---
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_USERNAME', 'user@example.com');
define('SMTP_PASSWORD', 'your_smtp_password');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'noreply@example.com');
define('SMTP_FROM_NAME', 'Akdeniz Üniversitesi Yemekhane');

// --- JWT (JSON Web Token) Ayarları ---
define('JWT_SECRET_KEY', 'SizinCokGuvenliVeTahminEdilemezAnahtarinizBurayaGelecek'); 
define('JWT_EXPIRATION_TIME', 3600); // Saniye cinsinden (1 saat)

define('PHP_EXECUTABLE_PATH', '/Applications/MAMP/bin/php/php8.4.1/bin/php');



