<?php
// includes/functions.php

/**
 * Gelişmiş işlem kaydı (log) oluşturur.
 *
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param string $username İşlemi yapan yönetici.
 * @param string $action_type Eylemin türü (örn: 'LOGIN', 'MEAL_UPDATE', 'DATE_DELETE').
 * @param string $action_summary Eylemin kısa özeti (örn: "Yemek Silindi").
 * @param string $details Eylemle ilgili detaylı bilgi (örn: "Yemek Adı: Mercimek Çorbası, ID: 15").
 */
function create_log($pdo, $username, $action_type, $action_summary, $details = '') {
    // Kullanıcının IP adresini al
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO logs (admin_username, ip_address, action_type, action_summary, details) 
             VALUES (:username, :ip_address, :action_type, :action_summary, :details)"
        );
        $stmt->execute([
            ':username' => $username,
            ':ip_address' => $ip_address,
            ':action_type' => $action_type,
            ':action_summary' => $action_summary,
            ':details' => $details
        ]);
    } catch (PDOException $e) {
        // Loglama hatası ana işlemi durdurmamalı, sadece hatayı kaydetmeli.
        error_log("Loglama hatası: " . $e->getMessage());
    }
}
