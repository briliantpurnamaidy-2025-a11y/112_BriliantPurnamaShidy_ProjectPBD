<?php
/** CLI SAJA. Tidak membuat perubahan DB. Cetak SQL hash untuk ditempel manual di HeidiSQL. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Jalankan melalui terminal PHP CLI.'); }
$username = trim((string)readline('Username yang akan diubah (admin_pro/kasir/manajer): '));
if (!in_array($username, ['admin_pro','kasir','manajer'], true)) exit("Username tidak diizinkan.\n");
$password = (string)readline('Password BARU (minimal 10 karakter; input mungkin terlihat): ');
if (strlen($password) < 10) exit("Minimal 10 karakter.\n");
$hash = password_hash($password, PASSWORD_BCRYPT);
if ($hash === false) exit("Gagal menghasilkan hash.\n");
echo "\nSALIN SQL BERIKUT ke HeidiSQL dan jalankan MANUAL:\n";
echo "UPDATE `user` SET `password`='" . $hash . "' WHERE `username`='".$username."';\n";
echo "\nJangan simpan password di source code. Script ini tidak terhubung ke MySQL.\n";
