<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/view_page.php';

tampilkanHalamanView('role', 'SELECT `idrole`, `nama_role` FROM `v_master_role`');
