<?php
/**
 * File mandiri: detail_pengadaan.php
 * Menampilkan data dari SQL View v_detail_pengadaan (read-only).
 * SQL SELECT khusus halaman ini, bukan dari parameter index.php?view=...
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/view_page.php';

tampilkanHalamanView('detail_pengadaan', 'SELECT `iddetail_pengadaan`, `idpengadaan`, `nama_barang`, `jumlah`, `harga_satuan`, `sub_total` FROM `v_detail_pengadaan`');
