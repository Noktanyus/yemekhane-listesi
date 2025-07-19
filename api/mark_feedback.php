<?php
require_once __DIR__ . '/bootstrap.php';

verify_csrf_token_and_exit();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']));
}

$feedback_id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? 'okundu'; // Varsayılan olarak 'okundu' yap

if (!$feedback_id) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Geri bildirim ID\'si eksik.']));
}

try {
    $stmt = $pdo->prepare("UPDATE feedback SET is_read = 1, status = ? WHERE id = ?");
    $stmt->execute([$status, $feedback_id]);

    if ($stmt->rowCount() > 0) {
        log_action('feedback_status_change', $admin_username, "Geri bildirim (ID: {$feedback_id}) durumu '{$status}' olarak değiştirildi.");
        echo json_encode(['success' => true, 'message' => 'Geri bildirim durumu güncellendi.']);
    } else {
        // ID bulunamadı veya durum zaten aynıydı
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Geri bildirim bulunamadı veya durum zaten güncel.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Mark feedback error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Durum güncellenirken bir veritabanı hatası oluştu.']));
}
