<?php
require_once __DIR__ . '/bootstrap.php';

$report_type = $_GET['type'] ?? 'top_meals';

if ($report_type === 'top_meals') {
    try {
        $stmt = $pdo->query("
            SELECT 
                ml.name, 
                COUNT(m.meal_id) as count
            FROM menus m
            JOIN meals ml ON m.meal_id = ml.id
            GROUP BY m.meal_id
            ORDER BY count DESC
            LIMIT 10
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Chart.js'in beklediği formata dönüştür
        $response = [
            'labels' => array_column($data, 'name'),
            'datasets' => [[
                'label' => 'Kaç Kez Çıktı',
                'data' => array_column($data, 'count'),
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                    'rgba(199, 199, 199, 0.2)',
                    'rgba(83, 102, 255, 0.2)',
                    'rgba(40, 159, 64, 0.2)',
                    'rgba(255, 99, 132, 0.2)'
                ],
                'borderColor' => [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)',
                    'rgba(83, 102, 255, 1)',
                    'rgba(40, 159, 64, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                'borderWidth' => 1
            ]]
        ];

        echo json_encode(['success' => true, 'data' => $response]);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Rapor verisi çekme hatası: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Rapor verileri alınırken bir hata oluştu.']);
    }
}
