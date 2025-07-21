<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

// Define allowed domains
const ALLOWED_DOMAINS = ['akdeniz.edu.tr', 'ogr.akdeniz.edu.tr'];
// Define upload settings
const UPLOAD_DIR = __DIR__ . '/../uploads/feedback/';
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']));
}

// --- Cloudflare Turnstile Validation ---
if (!function_exists('curl_init')) {
    error_log("cURL extension is not installed or enabled.");
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Sunucu yapılandırma hatası: cURL etkin değil.']));
}

$turnstile_response = $_POST['cf-turnstile-response'] ?? null;
$remote_ip = $_SERVER['REMOTE_ADDR'];

if (!$turnstile_response) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'CAPTCHA doğrulaması eksik. Lütfen sayfayı yenileyip tekrar deneyin.']));
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'secret' => CLOUDFLARE_SECRET_KEY,
    'response' => $turnstile_response,
    'remoteip' => $remote_ip,
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response_body = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response_body === false) {
    error_log("cURL Error during Turnstile verification: " . $curl_error);
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'CAPTCHA sunucusuna ulaşılamadı.']));
}

$result = json_decode($response_body, true);

if (!isset($result['success']) || $result['success'] !== true) {
    error_log('Cloudflare Turnstile verification failed. Response: ' . $response_body);
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'CAPTCHA doğrulaması başarısız. Lütfen tekrar deneyin.']));
}

// --- Input Validation ---
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 5]]);
$comment = trim($_POST['comment'] ?? '');
$image = $_FILES['image'] ?? null;
$image_path = null;

// Check for required fields
if (empty($name) || empty($email) || empty($comment) || $rating === false) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Lütfen tüm zorunlu alanları (ad, e-posta, değerlendirme, yorum) doldurun.']));
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Lütfen geçerli bir e-posta adresi girin.']));
}

// Validate email domain
$domain = substr(strrchr($email, "@"), 1);
if (!in_array($domain, ALLOWED_DOMAINS, true)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Sadece @akdeniz.edu.tr ve @ogr.akdeniz.edu.tr uzantılı e-posta adresleri kabul edilmektedir.']));
}

// --- File Upload Handling ---
if ($image && $image['error'] === UPLOAD_ERR_OK) {
    if ($image['size'] > MAX_FILE_SIZE) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Dosya boyutu 10 MB\'ı aşamaz.']));
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $image['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime_type, ALLOWED_MIME_TYPES, true)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Geçersiz dosya türü. Sadece JPG, PNG, GIF ve WEBP dosyalarına izin verilir.']));
    }
    $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
    $safe_filename = uniqid('', true) . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($extension);
    $destination = UPLOAD_DIR . $safe_filename;
    if (!move_uploaded_file($image['tmp_name'], $destination)) {
        http_response_code(500);
        error_log("File upload error: Failed to move uploaded file to {$destination}");
        die(json_encode(['success' => false, 'message' => 'Dosya yüklenirken bir sunucu hatası oluştu.']));
    }
    $image_path = $safe_filename; // Sadece dosya ad��nı kaydet
} elseif ($image && $image['error'] !== UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Dosya yüklenirken bir hata oluştu. Hata kodu: ' . $image['error']]));
}

// --- Database Insertion ---
try {
    $stmt = $pdo->prepare(
        "INSERT INTO feedback (name, email, rating, comment, image_path) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $email, $rating, $comment, $image_path]);
    echo json_encode(['success' => true, 'message' => 'Geri bildiriminiz başarıyla gönderildi.']);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Feedback submission database error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Veritabanı hatası nedeniyle geri bildirim gönderilemedi.']));
}
