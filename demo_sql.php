<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireAccess('dashboard');
require_once __DIR__ . '/includes/layout.php';

$section = (string)($_GET['section'] ?? 'view');
if (!in_array($section, ['view', 'sp', 'function', 'trigger'], true)) $section = 'view';

$views = [];
foreach (semuaModulView() as $key => $m) {
    if (roleCan($key)) $views[$key] = ['name' => $m['view'], 'title' => $m['title']];
}
if (roleCan('margin')) $views['margin_semua'] = ['name' => 'v_margin_semua', 'title' => 'Margin semua status'];
$spNames = [
    'sp_tampil_barang_per_satuan' => ['label' => 'Filter barang per satuan', 'parameter' => 'ID Satuan'],
    'sp_riwayat_stok_per_barang' => ['label' => 'Riwayat stok per barang', 'parameter' => 'ID Barang'],
];
$fnNames = [
    'fn_hitung_harga_jual' => ['label' => 'Harga beli + markup %', 'a' => 'Harga beli', 'b' => 'Markup (%)'],
    'fn_total_dengan_ppn' => ['label' => 'Subtotal + PPN %', 'a' => 'Subtotal', 'b' => 'PPN (%)'],
];
$expectedTriggers = [
    'trg_kartu_stok_masuk' => ['table' => 'detail_penerimaan', 'link' => 'penerimaan_tambah.php', 'menu' => 'penerimaan', 'label' => 'INSERT penerimaan'],
    'trg_kartu_stok_keluar' => ['table' => 'detail_penjualan', 'link' => 'penjualan_tambah.php', 'menu' => 'penjualan', 'label' => 'INSERT penjualan'],
    'trg_kartu_stok_retur' => ['table' => 'detail_retur', 'link' => 'retur_tambah.php', 'menu' => 'retur', 'label' => 'INSERT retur'],
];

$error = null;
$rows = [];
$sourceSql = '';
$sourceObject = '';
$dataOptions = [];
$installedTriggers = [];
$ledger = [];
$didRun = false;
try {
    $pdo = db();
    if ($section === 'view') {
        $selected = (string)($_GET['object'] ?? (isset($views['barang']) ? 'barang' : (string)array_key_first($views)));
        if (!isset($views[$selected])) $selected = (string)array_key_first($views);
        if (!$selected) throw new RuntimeException('Tidak ada View yang boleh diakses akun ini.');
        // Sumber nama View hanya dari whitelist internal, bukan SQL bebas dari GET.
        $sourceObject = $views[$selected]['name'];
        $where = in_array($selected, ['role','user'], true) ? ' WHERE idrole IN (1,3,4)' : '';
        $sourceSql = 'SELECT * FROM `' . $sourceObject . '`' . $where . ' LIMIT 100';
        $rows = $pdo->query($sourceSql)->fetchAll(PDO::FETCH_ASSOC);
        $didRun = true;
    } elseif ($section === 'sp') {
        $selected = (string)($_GET['object'] ?? 'sp_tampil_barang_per_satuan');
        if (!isset($spNames[$selected])) $selected = 'sp_tampil_barang_per_satuan';
        $sourceObject = $selected;
        $optionSql = $selected === 'sp_tampil_barang_per_satuan'
            ? 'SELECT idsatuan AS id, nama_satuan AS label FROM v_master_satuan ORDER BY nama_satuan'
            : 'SELECT idbarang AS id, nama_barang AS label FROM v_master_barang ORDER BY nama_barang';
        $dataOptions = $pdo->query($optionSql)->fetchAll(PDO::FETCH_ASSOC);
        if (isset($_GET['id']) && (string)$_GET['id'] !== '') {
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) throw new RuntimeException('Parameter ID harus bilangan bulat positif.');
            $sourceSql = 'CALL ' . $selected . '(' . (int)$id . ')';
            $stmt = $pdo->prepare('CALL ' . $selected . '(?)');
            $stmt->execute([(int)$id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $didRun = true;
        }
    } elseif ($section === 'function') {
        $selected = (string)($_GET['object'] ?? 'fn_hitung_harga_jual');
        if (!isset($fnNames[$selected])) $selected = 'fn_hitung_harga_jual';
        $sourceObject = $selected;
        if (isset($_GET['a'], $_GET['b'])) {
            $rawA = $_GET['a']; $rawB = $_GET['b'];
            if (!is_scalar($rawA) || !is_scalar($rawB) || !is_numeric((string)$rawA) || !is_numeric((string)$rawB)) {
                throw new RuntimeException('Masukkan dua angka yang valid.');
            }
            $a = (float)$rawA; $b = (float)$rawB;
            if (!is_finite($a) || !is_finite($b) || $a < 0 || $a > 1000000000 || $b < 0 || $b > 100) {
                throw new RuntimeException('Nilai pertama 0–1 miliar dan persentase 0–100.');
            }
            $sourceSql = 'SELECT ' . $selected . '(' . $a . ', ' . $b . ') AS hasil';
            $stmt = $pdo->prepare('SELECT ' . $selected . '(?, ?) AS hasil');
            $stmt->execute([$a, $b]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $didRun = true;
        }
    } else {
        $sourceObject = 'information_schema.TRIGGERS + v_kartu_stok';
        $sourceSql = 'SELECT * FROM v_kartu_stok ORDER BY idkartu_stok DESC LIMIT 15';
        $objects = $pdo->query("SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE, ACTION_TIMING, EVENT_MANIPULATION FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME IN ('trg_kartu_stok_masuk','trg_kartu_stok_keluar','trg_kartu_stok_retur')")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($objects as $t) $installedTriggers[$t['TRIGGER_NAME']] = $t;
        $ledger = $pdo->query($sourceSql)->fetchAll(PDO::FETCH_ASSOC);
        $didRun = true;
    }
} catch (Throwable $e) {
    // Detail SQL tetap dicatat ke server log, jangan bocorkan kredensial atau stack trace.
    error_log('[PBD Demo SQL] ' . $e->getMessage());
    $error = $e instanceof RuntimeException ? $e->getMessage() : 'Query gagal. Periksa pemasangan objek SQL, struktur View, dan koneksi MySQL.';
}

function demoTable(array $data): void {
    if (!$data) { echo '<div class="empty-state">Query berhasil, tetapi belum menghasilkan baris data.</div>'; return; }
    $cols = array_keys($data[0]);
    echo '<div class="table-wrap"><table class="data-table"><thead><tr>';
    foreach ($cols as $col) echo '<th>' . viewEsc($col) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($cols as $col) echo '<td>' . viewEsc($row[$col] ?? null) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

$tabs = ['view' => 'SQL View', 'sp' => 'Stored Procedure', 'function' => 'Function', 'trigger' => 'Trigger'];
mulaiLayout('Demonstrasi SQL', 'demo_sql');
?>
<div class="page-header"><div><a class="breadcrumb-back" href="index.php">← Dashboard</a><span class="eyebrow">EKSEKUSI LANGSUNG MYSQL</span><h1>Demonstrasi Objek SQL</h1><p>Pilih objek, jalankan perintah, dan lihat hasil query asli yang dikembalikan database.</p></div></div>
<nav class="db-tabs" aria-label="Pilih kategori SQL">
<?php foreach ($tabs as $key => $label): ?><a class="<?= $key === $section ? 'active' : '' ?>" href="demo_sql.php?section=<?= $key ?>"><?= viewEsc($label) ?></a><?php endforeach; ?>
</nav>
<?php if ($error): ?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif; ?>
<section class="panel db-demo-panel"><div class="panel-heading"><div><h2><?= viewEsc($tabs[$section]) ?></h2><p>Database: <code>praktikumpbd</code></p></div><span class="db-read-badge"><?= $section === 'trigger' ? 'DML melalui form transaksi' : 'READ ONLY' ?></span></div>
<?php if ($section === 'view'): ?>
  <form class="db-demo-form" method="get"><input type="hidden" name="section" value="view"><label>Nama SQL View<select name="object">
    <?php foreach ($views as $key => $v): ?><option value="<?= viewEsc($key) ?>" <?= $selected === $key ? 'selected' : '' ?>><?= viewEsc($v['name']) ?></option><?php endforeach; ?>
  </select></label><button class="small-btn">Jalankan SELECT</button></form>
  <div class="db-result-label"><b><?= viewEsc($sourceObject) ?></b><span><?= count($rows) ?> baris · Maks. 100</span></div>
  <?php if (!$error) demoTable($rows); ?>
<?php elseif ($section === 'sp'): ?>
  <form class="db-demo-form" method="get"><input type="hidden" name="section" value="sp"><label>Stored Procedure<select name="object" id="spSelector" onchange="this.form.removeAttribute('data-unset'); this.form.submit()">
    <?php foreach ($spNames as $name => $meta): ?><option value="<?= viewEsc($name) ?>" <?= $selected === $name ? 'selected' : '' ?>><?= viewEsc($name) ?></option><?php endforeach; ?>
  </select></label><label><?= viewEsc($spNames[$selected]['parameter']) ?><select name="id" required><option value="">Pilih parameter...</option>
  <?php foreach ($dataOptions as $item): ?><option value="<?= (int)$item['id'] ?>" <?= isset($_GET['id']) && (string)$_GET['id'] === (string)$item['id'] ? 'selected' : '' ?>><?= viewEsc($item['id'] . ' · ' . $item['label']) ?></option><?php endforeach; ?></select></label><button class="small-btn">Jalankan CALL</button></form>
  <?php if (!$didRun && !$error): ?><div class="empty-state">Pilih parameter lalu klik <strong>Jalankan CALL</strong> untuk menampilkan hasil Stored Procedure.</div><?php elseif (!$error): ?><div class="db-result-label"><b><?= viewEsc($sourceObject) ?></b><span><?= count($rows) ?> baris</span></div><?php demoTable($rows); endif; ?>
<?php elseif ($section === 'function'): ?>
  <form class="db-demo-form" method="get"><input type="hidden" name="section" value="function"><label>Function<select name="object" onchange="this.form.removeAttribute('data-unset'); this.form.submit()">
    <?php foreach ($fnNames as $name => $meta): ?><option value="<?= viewEsc($name) ?>" <?= $selected === $name ? 'selected' : '' ?>><?= viewEsc($name) ?></option><?php endforeach; ?>
  </select></label><label><?= viewEsc($fnNames[$selected]['a']) ?><input type="number" name="a" min="0" max="1000000000" step="0.01" value="<?= viewEsc($_GET['a'] ?? 12500) ?>" required></label><label><?= viewEsc($fnNames[$selected]['b']) ?><input type="number" name="b" min="0" max="100" step="0.01" value="<?= viewEsc($_GET['b'] ?? ($selected === 'fn_total_dengan_ppn' ? 12 : 20)) ?>" required></label><button class="small-btn">Hitung dengan MySQL</button></form>
  <?php if (!$didRun && !$error): ?><div class="empty-state">Isi parameter lalu klik <strong>Hitung dengan MySQL</strong>.</div><?php elseif (!$error): ?><div class="db-result-label"><b><?= viewEsc($sourceObject) ?></b><span>Perhitungan oleh Function MySQL</span></div><?php demoTable($rows); endif; ?>
<?php else: ?>
  <div class="db-trigger-grid">
  <?php foreach ($expectedTriggers as $name => $details): $obj = $installedTriggers[$name] ?? null; ?>
    <div class="db-trigger-card"><span class="db-object-kind">AFTER INSERT</span><strong><?= viewEsc($name) ?></strong><small>Tabel: <code><?= viewEsc($details['table']) ?></code></small><em class="<?= $obj ? 'db-ok' : 'db-check' ?>"><?= $obj ? '● Terpasang di MySQL' : '○ Belum ditemukan' ?></em>
    <?php if (roleCan($details['menu'], 'write')): ?><a href="<?= viewEsc($details['link']) ?>" class="db-outline">Lakukan <?= viewEsc($details['label']) ?> ↗</a><?php else: ?><small>Tidak ada izin INSERT pada akun ini.</small><?php endif; ?></div>
  <?php endforeach; ?>
  </div>
  <div class="db-trigger-note">Trigger MySQL dieksekusi ketika <code>INSERT</code> dilakukan pada tabel sumber, bukan ketika halaman ini dibuka. Gunakan tombol transaksi sesuai hak akses, lalu kembali dan klik <b>Perbarui hasil</b> untuk melihat bukti di <code>v_kartu_stok</code>.</div>
  <div class="db-result-label"><b>Bukti perubahan: v_kartu_stok</b><a href="demo_sql.php?section=trigger" class="db-outline">↻ Perbarui hasil</a></div>
  <?php if (!$error) demoTable($ledger); ?>
  <div class="db-inline-actions"><a class="db-outline" href="kartu_stok.php">Buka riwayat lengkap ↗</a></div>
<?php endif; ?>
<?php if ($sourceSql): ?><details class="db-query"><summary>Perintah SQL yang digunakan</summary><pre><code><?= viewEsc($sourceSql) ?></code></pre></details><?php endif; ?>
</section>
<?php akhiriLayout(); ?>
