<?php
declare(strict_types=1);
require_once __DIR__ . '/modules.php';
require_once __DIR__ . '/auth.php';

function viewEsc($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function viewUang($amount): string
{
    return 'Rp' . number_format((float)$amount, 0, ',', '.');
}
function viewTanggal($value): string
{
    if (!$value) return '—';
    try { return (new DateTime((string)$value))->format('d M Y, H:i'); }
    catch (Exception $e) { return (string)$value; }
}
function viewCell(string $column, $value): string
{
    if (in_array($column, ['harga','harga_satuan','harga_satuan_terima','sub_total','sub_total_terima','subtotal','subtotal_nilai','total_nilai'], true)) {
        return '<strong class="price">' . viewEsc(viewUang($value)) . '</strong>';
    }
    if (in_array($column, ['persen','persen_margin'], true)) {
        return viewEsc(number_format((float)$value, 2, ',', '.') . '%');
    }
    if (in_array($column, ['created_at','dibuat_pada'], true)) return viewEsc(viewTanggal($value));
    if ($column === 'status' && (string)$value === '1') return '<span class="pill pill-green">Aktif</span>';
    if ($column === 'status' && (string)$value === '0') return '<span class="pill pill-gray">Nonaktif</span>';
    if ($column === 'status' && in_array((string)$value, ['A','S'], true)) {
        return '<span class="pill ' . ($value === 'S' ? 'pill-green' : 'pill-yellow') . '">' . ($value === 'S' ? 'Selesai' : 'Proses') . '</span>';
    }
    return viewEsc($value);
}
function mulaiLayout(string $judul, string $aktif = 'dashboard', bool $demo = false): void
{
    $pengguna = requireLogin();
    $semua = semuaModulView();
    $utama = ['pengadaan','penerimaan','penjualan','kartu_stok','margin'];
    $dataMenu = ['barang', 'satuan', 'vendor', 'user', 'role'];
    $bukaData = in_array($aktif, $dataMenu, true);
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= viewEsc($judul) ?> | PBD</title>
  <link rel="stylesheet" href="assets/style.css">
  <?php if ($demo): ?><link rel="stylesheet" href="assets/responsi.css"><?php endif; ?>
  <link rel="stylesheet" href="assets/final.css">
  <link rel="stylesheet" href="assets/demo_db.css">
  <link rel="stylesheet" href="assets/clean.css">
  <link rel="stylesheet" href="assets/transaksi_gabungan.css">
  <script defer src="assets/app.js"></script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="index.php">PBD</a>
    <nav class="main-nav" aria-label="Menu">
      <a href="index.php" class="nav-item <?= $aktif === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
      <?php foreach (['pengadaan'=>'Pengadaan','penerimaan'=>'Penerimaan','penjualan'=>'Penjualan'] as $kunci=>$nama): ?>
        <?php if (roleCan($kunci)): ?>
          <a href="<?= viewEsc($kunci) ?>.php" class="nav-item <?= $aktif === $kunci ? 'active' : '' ?>"><?= viewEsc($nama) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if (roleCan('margin')): ?>
        <a href="margin_penjualan.php" class="nav-item <?= $aktif === 'margin' ? 'active' : '' ?>">Margin Penjualan</a>
      <?php endif; ?>
      <?php if (roleCan('kartu_stok')): ?>
        <a href="kartu_stok.php" class="nav-item <?= $aktif === 'kartu_stok' ? 'active' : '' ?>">Kartu Stok</a>
      <?php endif; ?>
      <details class="simple-nav-details" <?= $bukaData ? 'open' : '' ?>>
        <summary>Data Lainnya</summary>
        <div class="simple-nav-sub">
        <?php foreach ($dataMenu as $key): ?>
          <?php if (!isset($semua[$key]) || !roleCan($key)) continue; ?>
          <a class="nav-item <?= $aktif === $key ? 'active' : '' ?>" href="<?= viewEsc($semua[$key]['file']) ?>"><?= viewEsc($semua[$key]['title']) ?></a>
        <?php endforeach; ?>
        </div>
      </details>
    </nav>
  </aside>
  <div class="app-main">
    <header class="topbar">
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka menu">☰</button>
      <strong class="topbar-title"><?= viewEsc($judul) ?></strong>
      <div class="top-right">
        <span class="session-user"><?= viewEsc($pengguna['username']) ?> · <?= viewEsc($pengguna['nama_role']) ?></span>
        <form action="logout.php" method="post" class="logout-form">
          <input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>">
          <button type="submit" class="logout-button">Keluar</button>
        </form>
      </div>
    </header>
    <main class="content">
<?php
}
function akhiriLayout(): void
{ ?>
    </main>
  </div>
</div>
</body>
</html>
<?php }
