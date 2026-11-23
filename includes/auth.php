<?php
declare(strict_types=1);

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true, 'samesite' => 'Lax',
        ]);
    }
    session_start();
}
function currentUser(): ?array
{
    startAppSession();
    if (empty($_SESSION['auth_iduser']) || empty($_SESSION['auth_idrole'])) return null;
    if (time() - (int)($_SESSION['auth_last_activity'] ?? 0) > 7200) {
        unset($_SESSION['auth_iduser'], $_SESSION['auth_idrole'], $_SESSION['auth_username'], $_SESSION['auth_nama_role'], $_SESSION['auth_last_activity']);
        return null;
    }
    $_SESSION['auth_last_activity'] = time();
    return [
        'iduser' => (int)$_SESSION['auth_iduser'],
        'idrole' => (int)$_SESSION['auth_idrole'],
        'username' => (string)$_SESSION['auth_username'],
        'nama_role' => (string)$_SESSION['auth_nama_role'],
    ];
}
function requireLogin(): array
{
    $user = currentUser();
    if ($user === null) {
        header('Location: login.php', true, 302);
        exit;
    }
    return $user;
}
function roleCan(string $menu, string $aksi='read'): bool
{
    $u = currentUser();
    if ($u === null) return false;
    $r = (int)$u['idrole'];
    // Role 2 (Petugas Gudang) ditolak oleh login; tidak dipakai sebagai pengguna aplikasi.
    if ($r === 1) return true;
    $read = [
        'dashboard' => [3,4], 'barang' => [3,4], 'satuan' => [3,4],
        'vendor' => [4], 'role' => [4], 'user' => [4],
        'margin' => [4], 'pengadaan' => [4], 'detail_pengadaan' => [4],
        'penerimaan' => [4], 'detail_penerimaan' => [4],
        'penjualan' => [3,4], 'detail_penjualan' => [3,4],
        'kartu_stok' => [3,4], 'retur' => [4], 'detail_retur' => [4],
    ];
    $write = [
        'pengadaan' => [4], 'margin' => [4], 'penjualan' => [3],
    ];
    return in_array($r, ($aksi === 'write' ? ($write[$menu] ?? []) : ($read[$menu] ?? [])), true);
}
function requireAccess(string $menu, string $aksi='read'): array
{
    $u = requireLogin();
    if (!roleCan($menu, $aksi)) {
        http_response_code(403);
        exit('Akses ditolak. Anda tidak memiliki izin untuk halaman ini. <a href="index.php">Kembali ke dashboard</a>.');
    }
    return $u;
}
function appCsrf(): string
{
    startAppSession();
    if (empty($_SESSION['app_csrf'])) $_SESSION['app_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['app_csrf'];
}
function verifyAppCsrf(): void
{
    $posted = $_POST['csrf'] ?? null;
    if (!is_string($posted) || !hash_equals(appCsrf(), $posted)) {
        http_response_code(403);
        throw new RuntimeException('Formulir kedaluwarsa. Muat ulang halaman sebelum mengirim.');
    }
}
function flashSet(string $message, string $type='success'): void
{
    startAppSession();
    $_SESSION['flash_app'] = ['message' => $message, 'type' => $type];
}
function flashTake(): ?array
{
    startAppSession();
    $f = $_SESSION['flash_app'] ?? null;
    unset($_SESSION['flash_app']);
    return is_array($f) ? $f : null;
}
function redirectTo(string $url): never
{
    // URL internal dan relatif saja; jangan menerima URL dari input pengguna.
    header('Location: ' . $url, true, 303);
    exit;
}
