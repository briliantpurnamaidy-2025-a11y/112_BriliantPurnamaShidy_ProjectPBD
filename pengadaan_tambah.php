<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/ops.php';
require_once __DIR__ . '/includes/layout.php';
$user = requireAccess('pengadaan','write');
$error = '';
$pdo = db();
$vendors = $pdo->query("SELECT idvendor,nama_vendor FROM v_master_vendor WHERE status='1' ORDER BY nama_vendor")->fetchAll();
$products = $pdo->query('SELECT idbarang,nama_barang,harga,nama_satuan FROM v_master_barang WHERE status=1 ORDER BY nama_barang')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyAppCsrf();
        $vendorId = idPos($_POST['idvendor'] ?? '', 'Vendor');
        $ids = array_map('intval',array_column($vendors,'idvendor'));
        if (!in_array($vendorId,$ids,true)) throw new RuntimeException('Pilih vendor yang aktif.');
        $items = getMultiItems();
        $allowed = array_map('intval',array_column($products,'idbarang'));
        $subtotal=0;
        foreach ($items as $it) {
            if (!in_array($it['idbarang'],$allowed,true)) throw new RuntimeException('Barang tidak aktif atau tidak ditemukan.');
            $subtotal += $it['subtotal'];
        }
        $totals = calculateTaxTotal($pdo,$subtotal,12);
        $pdo->beginTransaction();
        $q=$pdo->prepare("INSERT INTO pengadaan (user_iduser,vendor_idvendor,status,subtotal_nilai,ppn,total_nilai) VALUES (?,?,'A',0,0,0)");
        $q->execute([$user['iduser'],$vendorId]);
        $orderId=(int)$pdo->lastInsertId();
        $q=$pdo->prepare('INSERT INTO detail_pengadaan(idpengadaan,idbarang,jumlah,harga_satuan,sub_total) VALUES (?,?,?,?,?)');
        foreach ($items as $it) $q->execute([$orderId,$it['idbarang'],$it['jumlah'],$it['harga_satuan'],$it['subtotal']]);
        $q=$pdo->prepare('UPDATE pengadaan SET subtotal_nilai=?,ppn=?,total_nilai=? WHERE idpengadaan=?');
        $q->execute([$totals['subtotal'],$totals['ppn'],$totals['total'],$orderId]);
        $pdo->commit();
        flashSet('Pengadaan #'.$orderId.' disimpan. Status Proses; stok belum berubah.');
        redirectTo('pengadaan.php?id='.$orderId.'#transaksi-'.$orderId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error=dbError($e);
    }
}
mulaiLayout('Tambah Pengadaan','pengadaan');
?>
<div class="page-header"><div><a class="breadcrumb-back" href="pengadaan.php">← Daftar Pengadaan</a><h1>Buat Pengadaan<span class="accent">.</span></h1><p>Pesan barang kepada vendor. Stok baru berubah saat barang diterima.</p></div></div>
<?php if ($error): ?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif; ?>
<section class="panel"><div class="panel-heading"><h2>Rincian pesanan</h2></div>
<form class="tx-form" method="post" autocomplete="off">
<input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>">
<div class="form-grid"><label>Vendor<select name="idvendor" required><option value="">Pilih vendor</option><?php foreach($vendors as $v): ?><option value="<?= (int)$v['idvendor'] ?>" <?= (string)($_POST['idvendor']??'')===(string)$v['idvendor']?'selected':'' ?>><?= viewEsc($v['nama_vendor']) ?></option><?php endforeach; ?></select></label><label>Petugas<input value="<?= viewEsc($user['username']) ?>" disabled><span class="form-hint">Dari akun yang sedang login.</span></label></div>
<h3 class="section-spacing">Barang yang dipesan</h3>
<div class="form-row"><strong>Barang</strong><strong>Jumlah</strong><strong>Harga beli / satuan</strong><strong></strong></div>
<div id="itemRows" data-item-list="pengadaan">
<?php for($i=0;$i<3;$i++): ?><div class="form-row item-row"><label>Barang<select name="idbarang[]" class="product-pick"><option value="">Pilih barang</option><?php foreach($products as $b): ?><option value="<?= (int)$b['idbarang'] ?>" data-price="<?= (int)$b['harga'] ?>"><?= viewEsc($b['nama_barang'].' ('.$b['nama_satuan'].')') ?></option><?php endforeach; ?></select></label><label>Jumlah<input type="number" min="1" max="100000" name="jumlah[]" value="1"></label><label>Harga (Rp)<input type="number" min="0" max="100000000" name="harga_satuan[]" class="unit-price" placeholder="Contoh: 12500"></label><button type="button" class="remove-row" title="Hapus baris">×</button></div><?php endfor; ?>
</div>
<div class="toolbar"><button type="button" class="small-btn" data-add-item>+ Tambah barang</button><span class="form-hint">Subtotal dihitung dari jumlah × harga satuan, ditambah PPN pengadaan 12%.</span></div>
<div class="form-actions"><button type="submit" class="primary-btn">Simpan Pengadaan</button><a href="pengadaan.php">Batal</a></div>
</form></section>
<?php akhiriLayout(); ?>
