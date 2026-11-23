<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';
startAppSession();
if (currentUser() !== null) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyAppCsrf();
        $attempts = (int)($_SESSION['login_attempts'] ?? 0);
        $last = (int)($_SESSION['login_last_attempt'] ?? 0);
        if ($attempts >= 6 && time() - $last < 120) throw new RuntimeException('Terlalu banyak percobaan. Silakan coba lagi beberapa saat.');
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || $password === '') throw new RuntimeException('Masukkan username dan password.');
        $stmt = db()->prepare('SELECT u.iduser,u.username,u.password,u.idrole,r.nama_role FROM `user` u JOIN `role` r ON r.idrole=u.idrole WHERE u.username=? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !in_array((int)$row['idrole'], [1,3,4], true) || !password_verify($password, (string)$row['password'])) {
            $_SESSION['login_attempts'] = $attempts + 1;
            $_SESSION['login_last_attempt'] = time();
            throw new RuntimeException('Username atau password tidak sesuai, atau akun tidak memiliki akses.');
        }
        session_regenerate_id(true);
        $_SESSION['auth_iduser'] = (int)$row['iduser'];
        $_SESSION['auth_idrole'] = (int)$row['idrole'];
        $_SESSION['auth_username'] = (string)$row['username'];
        $_SESSION['auth_nama_role'] = (string)$row['nama_role'];
        $_SESSION['auth_last_activity'] = time();
        unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
        redirectTo('index.php');
    } catch (Throwable $e) {
        $error = $e instanceof PDOException ? 'Koneksi database gagal. Periksa pengaturan database.' : $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login | PBD</title>
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="assets/final.css">
<link rel="stylesheet" href="assets/demo_db.css">
<link rel="stylesheet" href="assets/clean.css">
</head>
<body class="login-body">
<form class="login-card" method="post" autocomplete="on">
  <h1>Login</h1>
  <?php if ($error): ?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>">
  <label>Username<input name="username" type="text" maxlength="45" required autocomplete="username" value="<?= viewEsc($_POST['username'] ?? '') ?>"></label>
  <label>Password<input name="password" type="password" required autocomplete="current-password"></label>
  <button type="submit" class="primary-btn login-button">Masuk</button>
</form>
</body>
</html>
