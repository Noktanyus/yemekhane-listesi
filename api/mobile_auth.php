<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Composer'ın autoload dosyası

use Firebase\JWT\JWT;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']));
}

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Kullanıcı adı ve şifre boş olamaz.']));
}

try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $issuedAt = time();
        $expirationTime = $issuedAt + JWT_EXPIRATION_TIME;

        $payload = [
            'iss' => 'localhost', // Issuer
            'aud' => 'localhost', // Audience
            'iat' => $issuedAt,                      // Issued at
            'exp' => $expirationTime,                // Expiration time
            'data' => [
                'id' => $admin['id'],
                'username' => $admin['username']
            ]
        ];

        $jwt = JWT::encode($payload, JWT_SECRET_KEY, 'HS256');

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Giriş başarılı.',
            'token' => $jwt,
            'expires_in' => JWT_EXPIRATION_TIME
        ]);

    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı adı veya şifre.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Mobile Auth Error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Giriş sırasında bir sunucu hatası oluştu.']));
}
