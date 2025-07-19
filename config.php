<?php
// config.php

// Hata raporlamayı geliştirme aşamasında açın, produksiyonda kapatın.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);


 define('DB_HOST', 'localhost');
 define('DB_NAME', 'akdeniz_yemekhane');
 define('DB_USER', 'root');
 define('DB_PASS', '');

// Cloudflare Turnstile Test Anahtarları
// Bu anahtarlar her zaman doğrulamayı başarılı kılar.
// Gerçek kullanım için Cloudflare panelinizden kendi anahtarlarınızı almalısınız.
define('CLOUDFLARE_SITE_KEY', '1x00000000000000000000AA');
define('CLOUDFLARE_SECRET_KEY', '1x0000000000000000000000000000000AA');

// Diğer uygulama ayarları
define('APP_URL', 'http://localhost/yemekhane-listesi');
define('APP_NAME', 'Akdeniz Üniversitesi Yemekhane Menüsü');

// --- E-posta (SMTP) Ayarları ---
// Geri bildirimleri cevaplamak için kullanılacak.
// PHPMailer gibi bir kütüphane ile kullanılması önerilir.
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'user@example.com');
define('SMTP_PASSWORD', 'your_password');
define('SMTP_FROM_EMAIL', 'noreply@example.com');
define('SMTP_FROM_NAME', APP_NAME);
