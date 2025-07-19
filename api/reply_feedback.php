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

// CSRF kontrolü
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid CSRF token.']));
}

$feedback_id = $_POST['id'] ?? null;
$reply_text = $_POST['reply_text'] ?? null;
$user_email = $_POST['email'] ?? null;

if (empty($feedback_id) || empty($reply_text) || empty($user_email)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Eksik parametreler.']));
}

$admin_username = $_SESSION['admin_username'] ?? 'Bilinmeyen Admin';

try {
    // 1. E-postayı gönder
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
    $mail->Body    = nl2br(htmlspecialchars($reply_text));
    $mail->AltBody = htmlspecialchars($reply_text);

    $mail->send();

    // 2. Veritabanını güncelle
    $stmt = $pdo->prepare(
        "UPDATE feedback 
         SET status = 'cevaplandı', reply_text = :reply_text, replied_by = :replied_by, replied_at = NOW() 
         WHERE id = :id"
    );
    $stmt->execute([
        ':reply_text' => $reply_text,
        ':replied_by' => $admin_username,
        ':id' => $feedback_id
    ]);

    // 3. Log kaydı oluştur
    log_action('Geri Bildirim Cevaplandı', "ID: {$feedback_id} olan geri bildirime cevap gönderildi.");

    echo json_encode(['success' => true, 'message' => 'Cevap başarıyla gönderildi ve kaydedildi.']);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Reply feedback error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'E-posta gönderilirken bir hata oluştu: ' . $mail->ErrorInfo]));
}
?>