<?php

require_once __DIR__ . '/bootstrap.php';

if (!defined('IS_MOBILE_API_CALL')) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']));
    }
    verify_csrf_token_and_exit();
}

$feedback_id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null; // 'mark_read', 'archive', 'unarchive'

if (!$feedback_id || !$action) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Geri bildirim ID\'si veya eylem eksik.']));
}

$admin_username = $_SESSION['admin_username'] ?? 'Bilinmeyen';
$sql = "";
$params = [];

switch ($action) {
    case 'mark_read':
        $sql = "UPDATE feedback SET is_read = 1 WHERE id = :id";
        $log_details = "Geri bildirim (ID: {$feedback_id}) okundu olarak işaretlendi.";
        break;
    case 'archive':
        $sql = "UPDATE feedback SET is_archived = 1 WHERE id = :id";
        $log_details = "Geri bildirim (ID: {$feedback_id}) arşivlendi.";
        break;
    case 'unarchive':
        $sql = "UPDATE feedback SET is_archived = 0 WHERE id = :id";
        $log_details = "Geri bildirim (ID: {$feedback_id}) arşivden çıkarıldı.";
        break;
    default:
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Geçersiz eylem.']));
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $feedback_id]);

    if ($stmt->rowCount() > 0) {
        log_action('feedback_status_change', $admin_username, $log_details);

        // İşlem sonrası güncel veriyi çek ve döndür
        $select_stmt = $pdo->prepare("SELECT f.*, a.username as replied_by_username FROM feedback f LEFT JOIN admins a ON f.replied_by = a.id WHERE f.id = :id");
        $select_stmt->execute([':id' => $feedback_id]);
        $updated_feedback = $select_stmt->fetch(PDO::FETCH_ASSOC);

        // Durumu hesapla
        if ($updated_feedback['is_archived']) {
            $updated_feedback['status'] = 'arsivlendi';
        } elseif (!empty($updated_feedback['reply_message'])) {
            $updated_feedback['status'] = 'cevaplandı';
        } elseif ($updated_feedback['is_read']) {
            $updated_feedback['status'] = 'okundu';
        } else {
            $updated_feedback['status'] = 'yeni';
        }
        $updated_feedback['created_at_formatted'] = date('d.m.Y H:i', strtotime($updated_feedback['created_at']));


        echo json_encode(['success' => true, 'message' => 'Geri bildirim durumu başarıyla güncellendi.', 'feedback' => $updated_feedback]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Geri bildirim bulunamadı veya durum zaten günceldi.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Mark feedback error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Durum güncellenirken bir veritabanı hatası oluştu.']));
}
