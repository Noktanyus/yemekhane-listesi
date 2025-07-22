<?php

// includes/functions.php

/**
 * Veritabanına bir işlem kaydı (log) ekler.
 *
 * @param string $action Yapılan işlemin açıklaması.
 * @param string $admin_username İşlemi yapan yöneticinin kullanıcı adı.
 * @param string $details İşlemle ilgili ek detaylar.
 */
function log_action($action_type, $admin_username, $details = '')
{
    require_once __DIR__ . '/../db_connect.php';
    global $pdo;
    
    // Eğer $pdo tanımlı değilse, bağlantıyı yeniden kur
    if (!isset($pdo) || $pdo === null) {
        require_once __DIR__ . '/../db_connect.php';
    }

    $ip_address = 'UNKNOWN';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO logs (admin_username, ip_address, action_type, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$admin_username, $ip_address, $action_type, $details]);
    } catch (PDOException $e) {
        error_log("Loglama Hatası: " . $e->getMessage());
    }
}
