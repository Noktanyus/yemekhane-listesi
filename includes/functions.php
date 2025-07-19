<?php
// includes/functions.php

/**
 * Veritabanına bir işlem kaydı (log) ekler.
 *
 * @param string $action Yapılan işlemin açıklaması.
 * @param string $admin_username İşlemi yapan yöneticinin kullanıcı adı.
 * @param string $details İşlemle ilgili ek detaylar.
 */
function log_action($action, $admin_username, $details = '') {
    global $pdo; // db_connect.php'den gelen global pdo nesnesini kullan

    // Kullanıcının gerçek IP adresini almayı dene
    $ip_address = 'UNKNOWN';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO logs (admin_username, ip_address, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$admin_username, $ip_address, $action, $details]);
    } catch (PDOException $e) {
        // Loglama hatası ana işlemi durdurmamalı, sadece hatayı kaydetmeli.
        error_log("Loglama Hatası: " . $e->getMessage());
    }
}
