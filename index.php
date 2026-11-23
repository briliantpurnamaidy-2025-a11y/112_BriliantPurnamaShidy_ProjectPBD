<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireAccess('dashboard');
require_once __DIR__ . '/includes/layout.php';

$marginAktif = null;
try {
    $hasil = db()->query('SELECT persen FROM margin_penjualan WHERE status = 1 ORDER BY idmargin_penjualan DESC LIMIT 1')->fetchColumn();
    if ($hasil !== false) $marginAktif = (float)$hasil;
} catch (Throwable $e) {
    error_log('[Dashboard Margin] ' . $e->getMessage());
}

$proses = [
    ['key' => 'pengadaan', 'nama' => 'Pengadaan', 'tambah' => 'pengadaan_tambah.php', 'daftar' => 'pengadaan.php'],
    ['key' => 'penerimaan', 'nama' => 'Penerimaan', 'tambah' => 'penerimaan_tambah.php', 'daftar' => 'penerimaan.php'],
    ['key' => 'penjualan', 'nama' => 'Penjualan', 'tambah' => 'penjualan_tambah.php', 'daftar' => 'penjualan.php'],
];
mulaiLayout('Dashboard', 'dashboard');
?>
<header class="clean-heading">
  <h1>Dashboard</h1>
</header>
<section class="clean-margin" aria-labelledby="margin-title">
  <div>
    <h2 id="margin-title">Margin Aktif</h2>
    <div class="clean-margin-value"><?= $marginAktif === null ? 'Belum diatur' : viewEsc(number_format($marginAktif, 2, ',', '.') . '%') ?></div>
  </div>
  <?php if (roleCan('margin')): ?>
    <a href="margin_penjualan.php" class="clean-secondary">Atur Margin</a>
  <?php endif; ?>
</section>

<section class="clean-process" aria-labelledby="process-title">
  <h2 id="process-title">Transaksi</h2>
  <div class="clean-process-grid">
    <?php foreach ($proses as $p): ?>
      <?php if (!roleCan($p['key'])) continue; ?>
      <article class="clean-process-card">
        <h3><?= viewEsc($p['nama']) ?></h3>
        <div class="clean-card-actions">
          <?php if (roleCan($p['key'], 'write')): ?>
            <a class="primary-btn" href="<?= viewEsc($p['tambah']) ?>">Tambah</a>
          <?php endif; ?>
          <a class="clean-secondary" href="<?= viewEsc($p['daftar']) ?>">Lihat Data</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php if (roleCan('kartu_stok')): ?>
  <a class="clean-stock-link" href="kartu_stok.php">Lihat Kartu Stok <span aria-hidden="true">→</span></a>
<?php endif; ?>
<?php akhiriLayout(); ?>
