<?php
// includes/csrf.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * CSRF token'ı oluşturur ve session'a kaydeder.
 * Eğer zaten bir token varsa onu döndürür.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Gönderilen CSRF token'ını doğrular.
 *
 * @param string $token Formdan gelen token.
 * @return bool Token geçerliyse true, değilse false.
 */
function validate_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        // Token kullanıldıktan sonra isteğe bağlı olarak yenilenebilir.
        // unset($_SESSION['csrf_token']); 
        return true;
    }
    return false;
}

/**
 * POST istekleri için CSRF token doğrulamasını çalıştırır.
 * Başarısız olursa script'i sonlandırır.
 */
function verify_csrf_token_and_exit() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        if (!validate_csrf_token($token)) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Geçersiz veya eksik CSRF token. Lütfen sayfayı yenileyip tekrar deneyin.']));
        }
    }
}
