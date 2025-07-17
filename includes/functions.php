<?php
// includes/functions.php

/**
 * Veritabanına bir işlem kaydı (log) ekler.
 *
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param string $username İşlemi yapan yöneticinin kullanıcı adı.
 * @param string $action Yapılan işlemin açıklaması (örn: YEMEK SİLİNDİ).
 * @param string $details İşlemle ilgili ek detaylar.
 */
function create_log($pdo, $username, $action, $details = '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (admin_username, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$username, $action, $details]);
    } catch (PDOException $e) {
        // Loglama hatası ana işlemi durdurmamalı, sadece hatayı kaydetmeli.
        error_log("Loglama hatası: " . $e->getMessage());
    }
}
