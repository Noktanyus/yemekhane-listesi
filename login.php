<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php'; // Cloudflare anahtarları için

// Brute Force Koruması
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_TIME = 300; // 5 dakika (saniye cinsinden)

function is_locked_out() {
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        if (isset($_SESSION['lockout_time']) && time() - $_SESSION['lockout_time'] < LOCKOUT_TIME) {
            return true;
        }
        // Lockout süresi dolduysa sıfırla
        unset($_SESSION['login_attempts']);
        unset($_SESSION['lockout_time']);
    }
    return false;
}

function record_failed_login() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['login_attempts']++;
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['lockout_time'] = time();
    }
}

function clear_login_attempts() {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['lockout_time']);
}

$error_message = '';

if (is_locked_out()) {
    $remaining_time = LOCKOUT_TIME - (time() - $_SESSION['lockout_time']);
    $error_message = "Çok fazla hatalı deneme. Lütfen {$remaining_time} saniye sonra tekrar deneyin.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Cloudflare Turnstile Validation ---
    if (!function_exists('curl_init')) {
        $error_message = 'Sunucu yapılandırma hatası: cURL etkin değil.';
        error_log($error_message);
    } else {
        $turnstile_response = $_POST['cf-turnstile-response'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (!$turnstile_response) {
            $error_message = 'CAPTCHA doğrulaması eksik. Lütfen tekrar deneyin.';
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret' => CLOUDFLARE_SECRET_KEY,
                'response' => $turnstile_response,
                'remoteip' => $ip
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response_data = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($response_data === false) {
                $error_message = 'CAPTCHA sunucusuna ulaşılamadı.';
                error_log("cURL Error during Turnstile verification on login: " . $curl_error);
            } else {
                $result = json_decode($response_data, true);
                if (!($result && $result['success'])) {
                    $error_message = 'CAPTCHA doğrulaması başarısız. Lütfen tekrar deneyin.';
                    error_log('Cloudflare Turnstile verification failed on login. Response: ' . $response_data);
                }
            }
        }
    }
    // --- End of Validation ---

    if (empty($error_message)) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error_message = 'Kullanıcı adı ve şifre boş olamaz.';
            record_failed_login();
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password_hash'])) {
                    clear_login_attempts();
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    header('Location: admin.php');
                    exit;
                } else {
                    $error_message = 'Geçersiz kullanıcı adı veya şifre.';
                    record_failed_login();
                }
            } catch (PDOException $e) {
                $error_message = "Veritabanı hatası: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi</title>
    <link rel="stylesheet" href="/yemekhane-listesi/assets/css/admin.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <link rel="icon" href="data:,">
</head>
<body>
    <div class="login-container">
        <form action="login.php" method="post" class="login-form">
            <h2>Yönetim Paneli Girişi</h2>
            <p>Lütfen devam etmek için giriş yapın.</p>
            
            <?php if (!empty($error_message)): ?>
                <div class="form-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username" class="sr-only">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" placeholder="Kullanıcı Adı" required>
            </div>
            <div class="form-group">
                <label for="password" class="sr-only">Şifre</label>
                <input type="password" id="password" name="password" placeholder="Şifre" required>
            </div>
            
            <!-- Cloudflare Turnstile Widget -->
            <div class="cf-turnstile" data-sitekey="<?php echo CLOUDFLARE_SITE_KEY; ?>"></div>

            <button type="submit" class="btn-submit" <?php if (is_locked_out()) echo 'disabled'; ?>>Giriş Yap</button>
        </form>
    </div>
</body>
</html>