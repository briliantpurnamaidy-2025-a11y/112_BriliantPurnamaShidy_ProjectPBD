<?php
/**
 * File mandiri: role.php
 * Menampilkan data dari SQL View v_master_role (read-only).
 * SQL SELECT khusus halaman ini, bukan dari parameter index.php?view=...
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/view_page.php';

tampilkanHalamanView('role', 'SELECT `idrole`, `nama_role` FROM `v_master_role`');
