<?php
/**
 * File mandiri: detail_penjualan.php
 * Menampilkan data dari SQL View v_detail_penjualan (read-only).
 * SQL SELECT khusus halaman ini, bukan dari parameter index.php?view=...
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/view_page.php';

tampilkanHalamanView('detail_penjualan', 'SELECT `iddetail_penjualan`, `idpenjualan`, `nama_barang`, `jumlah`, `harga_satuan`, `subtotal` FROM `v_detail_penjualan`');
