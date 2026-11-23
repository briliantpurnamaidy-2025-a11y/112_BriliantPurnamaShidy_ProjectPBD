<?php
declare(strict_types=1);
require_once __DIR__.'/includes/ops.php';
require_once __DIR__.'/includes/layout.php';
requireAccess('kartu_stok');
$pdo=db();$rows=[];$error='';$selected=0;$barang=[];
try{
    $barang=$pdo->query('SELECT idbarang,nama_barang FROM v_master_barang ORDER BY nama_barang')->fetchAll();
    $raw=$_GET['idbarang']??'';
    if($raw!==''){
        $selected=idPos($raw,'Barang');
        // Pemanggilan Stored Procedure tidak ditampilkan sebagai menu pengujian.
        $q=$pdo->prepare('CALL sp_riwayat_stok_per_barang(?)');$q->execute([$selected]);$rows=$q->fetchAll();$q->closeCursor();
    }else{
        $rows=$pdo->query('SELECT idkartu_stok,nama_barang,jenis_transaksi,masuk,keluar,stock,created_at,idtransaksi,idbarang FROM v_kartu_stok ORDER BY idkartu_stok DESC LIMIT 500')->fetchAll();
    }
}catch(Throwable $e){$error=dbError($e);}
mulaiLayout('Kartu Stok','kartu_stok');
?>
<div class="page-header"><div><a href="index.php" class="breadcrumb-back">← Dashboard</a><h1>Kartu Stok<span class="accent">.</span></h1><p>Semua pergerakan barang masuk dan keluar tercatat otomatis.</p></div></div>
<?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif;?>
<section class="panel"><div class="panel-heading"><h2>Riwayat Stok</h2></div><form method="get" class="filter-bar"><label>Barang<select name="idbarang"><option value="">Semua barang (maksimal 500 catatan)</option><?php foreach($barang as $b):?><option value="<?= (int)$b['idbarang'] ?>" <?= $selected===(int)$b['idbarang']?'selected':'' ?>><?= viewEsc($b['nama_barang']) ?></option><?php endforeach;?></select></label><button class="small-btn">Tampilkan</button></form>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Tanggal</th><th>Barang</th><th>Aktivitas</th><th>Masuk</th><th>Keluar</th><th>Saldo</th><th>Referensi</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td><?= viewEsc(viewTanggal($row['created_at'])) ?></td><td><?= viewEsc($row['nama_barang']) ?></td><td><?= viewEsc(['M'=>'Penerimaan','K'=>'Penjualan','R'=>'Retur vendor'][$row['jenis_transaksi']]??$row['jenis_transaksi']) ?></td><td><?= (int)$row['masuk'] ?></td><td><?= (int)$row['keluar'] ?></td><td><strong><?= (int)$row['stock'] ?></strong></td><td>#<?= (int)$row['idtransaksi'] ?></td></tr><?php endforeach;?></tbody></table></div><?php if(!$rows):?><div class="empty-state">Belum ada aktivitas stok.</div><?php endif;?></section>
<?php akhiriLayout(); ?>
