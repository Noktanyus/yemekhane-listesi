<?php

require_once __DIR__ . '/bootstrap.php';

if (!defined('IS_MOBILE_API_CALL')) {
    // Sadece giriş yapmış adminlerin erişebilmesini sağla
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        die('Forbidden: Access denied.');
    }
}

$filename = $_GET['file'] ?? null;

// Güvenlik: Dosya adı boş olamaz ve dizin değiştirme girişimlerini engelle
if (empty($filename) || strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    http_response_code(400);
    die('Bad Request: Invalid filename.');
}

$filepath = __DIR__ . '/../uploads/feedback/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die('Not Found: Image does not exist.');
}

$mime_type = mime_content_type($filepath);
if (strpos($mime_type, 'image/') !== 0) {
    http_response_code(415);
    die('Unsupported Media Type: File is not an image.');
}

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($filepath));

// İndirme butonu için
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
}

readfile($filepath);
exit;
