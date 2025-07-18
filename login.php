<?php
session_start();
require_once 'db_connect.php';
require_once 'includes/functions.php';

$error_message = '';

// Eğer kullanıcı zaten giriş yapmışsa, admin paneline yönlendir
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Kullanıcı adı ve şifre boş bırakılamaz.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Giriş başarılı
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];

                // Başarılı giriş logu oluştur
                create_log($pdo, $admin['username'], 'LOGIN', 'Başarılı Yönetici Girişi', 'Kullanıcı başarıyla sisteme giriş yaptı.');

                header('Location: admin.php');
                exit;
            } else {
                // Giriş başarısız
                $error_message = 'Geçersiz kullanıcı adı veya şifre.';
            }
        } catch (PDOException $e) {
            $error_message = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - Akdeniz Üniversitesi Yemekhane</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="login-container">
        <form action="login.php" method="POST" class="login-form">
            <h2>Yönetici Paneli Girişi</h2>
            <p>Yemek Listesi Yönetim Sistemi</p>
            
            <?php if ($error_message): ?>
                <div class="form-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username" class="sr-only">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" placeholder="Kullanıcı Adı" required>
            </div>
            <div class="form-group">
                <label for="password" class="sr-only">Şifre</label>
                <input type="password" id="password" name="password" placeholder="Şifre" required>
            </div>
            <button type="submit" class="btn-submit">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
