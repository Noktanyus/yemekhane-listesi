<?php

require_once __DIR__ . '/bootstrap.php';

// PHPMailer'ı dahil et
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

if (!defined('IS_MOBILE_API_CALL')) {
    // CSRF kontrolü
    verify_csrf_token_and_exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

$feedback_id = $_POST['id'] ?? null;
$reply_message = $_POST['reply_text'] ?? null;
$user_email = $_POST['email'] ?? null;

if (empty($feedback_id) || empty($reply_message) || empty($user_email)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Eksik parametreler.']));
}

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_username = $_SESSION['admin_username'] ?? 'Bilinmeyen Admin';

if ($admin_id === null) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Cevap göndermek için yönetici oturumu gerekli.']));
}

try {
    // 1. E-postayı gönder (Bu kısım aynı kalabilir)
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($user_email);
    $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

    $mail->isHTML(true);
    $mail->Subject = 'Geri Bildiriminize Cevap';
    $mail->Body    = nl2br(htmlspecialchars($reply_message));
    $mail->AltBody = htmlspecialchars($reply_message);

    $mail->send();

    // 2. Veritabanını güncelle
    $stmt = $pdo->prepare(
        "UPDATE feedback 
         SET is_read = 1, reply_message = :reply_message, replied_by = :replied_by, replied_at = NOW() 
         WHERE id = :id"
    );
    $stmt->execute([
        ':reply_message' => $reply_message,
        ':replied_by' => $admin_id,
        ':id' => $feedback_id
    ]);

    // 3. Log kaydı oluştur
    log_action('feedback_replied', $admin_username, "Geri bildirim (ID: {$feedback_id}) cevaplandı.");

    echo json_encode(['success' => true, 'message' => 'Cevap başarıyla gönderildi ve kaydedildi.']);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Reply feedback error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'E-posta gönderilirken bir hata oluştu: ' . ($mail->ErrorInfo ?? $e->getMessage())]));
}
