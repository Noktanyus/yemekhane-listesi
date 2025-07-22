<?php

require_once 'bootstrap.php';

try {
    // Aktif yemek ücretlerini sıralama düzenine göre getir
    $stmt = $pdo->prepare("
        SELECT id, group_name, description, price, is_active, sort_order 
        FROM meal_prices 
        WHERE is_active = 1 
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute();
    $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $prices
    ]);

} catch (PDOException $e) {
    error_log("Yemek ücretleri getirme hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Yemek ücretleri alınırken bir hata oluştu.'
    ]);
}