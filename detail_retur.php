<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/view_page.php';

tampilkanHalamanView('detail_retur', 'SELECT `iddetail_retur`, `idretur`, `nama_barang`, `jumlah`, `alasan` FROM `v_detail_retur`');
