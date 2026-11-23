<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function idPos($v, string $label='ID'): int
{
    if (!is_scalar($v) || !ctype_digit((string)$v) || (int)$v < 1) throw new RuntimeException($label . ' tidak valid.');
    return (int)$v;
}
function integerAmount($value, string $name, int $min=0, int $max=100000000): int
{
    if (!is_scalar($value) || !ctype_digit((string)$value)) throw new RuntimeException($name . ' harus berupa bilangan bulat.');
    $v = (int)$value;
    if ($v < $min || $v > $max) throw new RuntimeException($name . ' di luar batas yang diizinkan.');
    return $v;
}
function getMultiItems(string $quantityKey='jumlah', string $priceKey='harga_satuan'): array
{
    $ids = $_POST['idbarang'] ?? null;
    $quantities = $_POST[$quantityKey] ?? null;
    $prices = $_POST[$priceKey] ?? null;
    if (!is_array($ids) || !is_array($quantities) || !is_array($prices) || count($ids) !== count($quantities) || count($ids) !== count($prices)) {
        throw new RuntimeException('Rincian transaksi tidak sesuai.');
    }
    $rows = [];
    foreach ($ids as $i => $rawId) {
        $id = (string)$rawId;
        $qty = (string)($quantities[$i] ?? '');
        $price = (string)($prices[$i] ?? '');
        if ($id === '' && ($price === '' || $price === '0') && ($qty === '' || $qty === '1')) continue;
        $id = idPos($id, 'Barang');
        if (isset($rows[$id])) throw new RuntimeException('Barang yang sama tidak boleh muncul dua kali dalam satu transaksi.');
        $qtyInt = integerAmount($qty, 'Jumlah', 1, 100000);
        $priceInt = integerAmount($price, 'Harga satuan', 0, 100000000);
        $subtotal = $qtyInt * $priceInt;
        if ($subtotal > 2000000000) throw new RuntimeException('Subtotal terlalu besar untuk kolom database INT.');
        $rows[$id] = ['idbarang' => $id, 'jumlah' => $qtyInt, 'harga_satuan' => $priceInt, 'subtotal' => $subtotal];
    }
    if (!$rows || count($rows) > 30) throw new RuntimeException('Isi 1 sampai 30 jenis barang dalam satu transaksi.');
    ksort($rows);
    return array_values($rows);
}
function calculateTaxTotal(PDO $pdo, int $subtotal, int $rate): array
{
    if ($subtotal < 0 || $subtotal > 1900000000) throw new RuntimeException('Total transaksi melewati batas kolom INT.');
    $st = $pdo->prepare('SELECT fn_total_dengan_ppn(?, ?)');
    $st->execute([$subtotal, $rate]);
    $total = (int)round((float)$st->fetchColumn());
    if ($total > 2000000000) throw new RuntimeException('Total dan pajak melewati batas kolom database.');
    return ['subtotal' => $subtotal, 'ppn' => $total - $subtotal, 'total' => $total];
}
function dbError(Throwable $e): string
{
    error_log('[Toko Gen Z] ' . $e->getMessage());
    if ($e instanceof PDOException) return 'Kesalahan database. Periksa urutan instalasi SQL dan log Laragon.';
    if ($e instanceof RuntimeException) return $e->getMessage();
    return 'Proses gagal. Pastikan database dan objek SQL sudah disiapkan, lalu lihat log Laragon untuk detail.';
}
function niceStatus($status): string
{
    if ((string)$status === 'S') return 'Selesai';
    if ((string)$status === 'A') return 'Proses';
    if ((string)$status === '1') return 'Aktif';
    if ((string)$status === '0') return 'Nonaktif';
    return (string)$status;
}
