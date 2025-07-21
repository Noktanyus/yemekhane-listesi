<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// --- Güvenlik: İzin verilen endpoint'lerin beyaz listesi ---
$allowed_endpoints = [
    'get_feedback',
    'get_logs',
    'get_menu_events',
    'get_report_data',
    'get_site_info',
    'get_week_overview',
    'manage_date',
    'manage_meal',
    'manage_officials',
    'mark_feedback',
    'reply_feedback',
    'copy_menu',
    'view_image'
    // 'upload_csv' gibi session'a bağımlı endpoint'ler mobil için doğrudan uyumlu olmayabilir.
];

$endpoint = $_GET['endpoint'] ?? '';

if (empty($endpoint) || !in_array($endpoint, $allowed_endpoints)) {
    http_response_code(400);
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Geçersiz veya eksik endpoint.']));
}

// --- JWT Doğrulama ve Kullanıcı Bilgisini Hazırlama ---
// Token'ı doğrulamaya çalışırız ama başarısız olursa isteği sonlandırmayız.
// Sorumluluk, çağrılan endpoint'e aittir.
$jwt = null;
$auth_header = $_SERVER['HTTP_X_AUTHORIZATION'] ?? (getallheaders()['X-Authorization'] ?? (getallheaders()['x-authorization'] ?? ''));
if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $jwt = $matches[1];
}

if ($jwt) {
    try {
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));
        // Token geçerli. Kullanıcı bilgilerini sonraki script'in kullanabilmesi için ayarla.
        $GLOBALS['current_admin_id'] = $decoded->data->id;
        $GLOBALS['current_admin_username'] = $decoded->data->username;
    } catch (Exception $e) {
        // Token geçersiz veya süresi dolmuş. Kullanıcıyı misafir olarak ayarla.
        $GLOBALS['current_admin_id'] = null;
        $GLOBALS['current_admin_username'] = null;
    }
} else {
    // Token hiç yok. Kullanıcıyı misafir olarak ayarla.
    $GLOBALS['current_admin_id'] = null;
    $GLOBALS['current_admin_username'] = null;
}

// --- Endpoint'i Dahil Etme ---

// Bu sabiti tanımlayarak, dahil edilen dosyanın bir mobil API çağrısı olduğunu anlamasını sağlıyoruz.
define('IS_MOBILE_API_CALL', true);

// Dahil edilen dosyanın içindeki $admin_username değişkenini JWT'den gelen bilgiyle dolduruyoruz.
$admin_username = $GLOBALS['current_admin_username'];

// İlgili API dosyasını dahil et
$file_to_include = __DIR__ . '/' . $endpoint . '.php';

if (file_exists($file_to_include)) {
    // Not: Dahil edilen dosya kendi header'larını ve yanıtını basacaktır.
    require $file_to_include;
} else {
    http_response_code(404);
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Endpoint dosyası sunucuda bulunamadı.']));
}
