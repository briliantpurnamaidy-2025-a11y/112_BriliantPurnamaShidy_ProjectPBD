<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/ops.php';
require_once __DIR__ . '/includes/layout.php';
$user=requireAccess('penjualan','write');
$pdo=db(); $error=''; $margin=null; $products=[];
try {
    $marginRows=$pdo->query('SELECT idmargin_penjualan,persen FROM v_margin_aktif')->fetchAll();
    if(count($marginRows)===1) $margin=$marginRows[0];
    $products=$pdo->query("SELECT b.idbarang,b.nama_barang,b.harga,b.nama_satuan,
      COALESCE((SELECT k.stock FROM kartu_stok k WHERE k.idbarang=b.idbarang ORDER BY k.idkartu_stok DESC LIMIT 1),0) AS stock
      FROM v_master_barang b WHERE b.status=1 ORDER BY b.nama_barang")->fetchAll();
    if($margin!==null){
        $q=$pdo->prepare('SELECT fn_hitung_harga_jual(?,?)');
        foreach($products as &$p){$q->execute([(int)$p['harga'],(float)$margin['persen']]);$p['harga_jual']=(int)round((float)$q->fetchColumn());}
        unset($p);
    }
} catch(Throwable $e){$error=dbError($e);}
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        verifyAppCsrf();
        // Validasi jumlah, harga ditentukan oleh database (bukan input browser).
        $ids=$_POST['idbarang']??null;$qtys=$_POST['jumlah']??null;
        if(!is_array($ids)||!is_array($qtys)||count($ids)!==count($qtys))throw new RuntimeException('Rincian penjualan tidak valid.');
        $requested=[];
        foreach($ids as $i=>$raw){
            if((string)$raw==='')continue;
            $id=idPos($raw,'Barang');
            if(isset($requested[$id])) throw new RuntimeException('Barang tidak boleh dipilih dua kali dalam transaksi.');
            $requested[$id]=integerAmount($qtys[$i]??'','Jumlah penjualan',1,100000);
        }
        if(!$requested||count($requested)>30)throw new RuntimeException('Pilih 1 sampai 30 barang untuk dijual.');
        ksort($requested);
        $pdo->beginTransaction();
        // Lock margin agar tidak berubah saat harga jual dihitung.
        $marginLock=$pdo->query('SELECT idmargin_penjualan,persen FROM margin_penjualan WHERE status=1 FOR UPDATE')->fetchAll();
        if(count($marginLock)!==1)throw new RuntimeException('Harus ada tepat satu margin aktif.');
        $m=$marginLock[0];
        $items=[];$subtotal=0;
        $qBarang=$pdo->prepare('SELECT idbarang,nama,harga,status FROM barang WHERE idbarang=? FOR UPDATE');
        $qStok=$pdo->prepare('SELECT COALESCE((SELECT stock FROM kartu_stok WHERE idbarang=? ORDER BY idkartu_stok DESC LIMIT 1),0)');
        $qHarga=$pdo->prepare('SELECT fn_hitung_harga_jual(?, ?)');
        foreach($requested as $id=>$qty){
            $qBarang->execute([$id]);$b=$qBarang->fetch();
            if(!$b||(int)$b['status']!==1)throw new RuntimeException('Barang tidak aktif atau tidak ditemukan (ID '.$id.').');
            $qStok->execute([$id]);$stock=(int)$qStok->fetchColumn();
            if($qty>$stock)throw new RuntimeException('Stok '.$b['nama'].' hanya '.$stock.'; permintaan '.$qty.'.');
            $qHarga->execute([(int)$b['harga'],(float)$m['persen']]);
            $price=(int)round((float)$qHarga->fetchColumn());
            $sub=$qty*$price;
            if($sub>2000000000)throw new RuntimeException('Nilai barang melebihi batas INT.');
            $items[]=['id'=>$id,'qty'=>$qty,'price'=>$price,'subtotal'=>$sub];
            $subtotal+=$sub;
        }
        $totals=calculateTaxTotal($pdo,$subtotal,11);
        $q=$pdo->prepare('INSERT INTO penjualan(iduser,idmargin_penjualan,subtotal_nilai,ppn,total_nilai) VALUES (?,?,0,0,0)');
        $q->execute([$user['iduser'],$m['idmargin_penjualan']]);$saleId=(int)$pdo->lastInsertId();
        $q=$pdo->prepare('INSERT INTO detail_penjualan(penjualan_idpenjualan,idbarang,jumlah,harga_satuan,subtotal) VALUES (?,?,?,?,?)');
        foreach($items as $it)$q->execute([$saleId,$it['id'],$it['qty'],$it['price'],$it['subtotal']]);
        // Trigger stok keluar wajib ada. Jika tidak ada, seluruh transaksi di-rollback.
        $q=$pdo->prepare("SELECT COUNT(*) FROM kartu_stok WHERE jenis_transaksi='K' AND idtransaksi=?");
        $q->execute([$saleId]);
        if((int)$q->fetchColumn()!==count($items))throw new RuntimeException('Pengurangan stok gagal. Periksa Trigger stok keluar. Transaksi dibatalkan.');
        $q=$pdo->prepare('UPDATE penjualan SET subtotal_nilai=?,ppn=?,total_nilai=? WHERE idpenjualan=?');
        $q->execute([$totals['subtotal'],$totals['ppn'],$totals['total'],$saleId]);
        $pdo->commit();
        flashSet('Penjualan #'.$saleId.' berhasil, total '.viewUang($totals['total']).'. Stok barang telah berkurang.');
        redirectTo('penjualan.php?id='.$saleId.'#transaksi-'.$saleId);
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=dbError($e);}
}
mulaiLayout('Buat Penjualan','penjualan');
?>
<div class="page-header"><div><a class="breadcrumb-back" href="penjualan.php">← Daftar Penjualan</a><h1>Buat Penjualan<span class="accent">.</span></h1><p>Harga jual dihitung otomatis menggunakan margin yang sedang aktif.</p></div></div>
<?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif;?>
<?php if(!$margin):?><div class="notice notice-error">Belum ada tepat satu margin aktif. Minta Administrator/Manajer mengatur margin dahulu.</div><?php endif;?>
<section class="panel"><div class="panel-heading"><h2>Rincian transaksi</h2></div>
<form method="post" class="tx-form">
<input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>">
<p class="form-hint">Kasir: <strong><?= viewEsc($user['username']) ?></strong>. Margin aktif: <strong><?= viewEsc($margin['persen']??'—') ?>%</strong>. PPN: <strong>11%</strong>.</p>
<div class="form-row"><strong>Barang dan stok</strong><strong>Jumlah beli</strong><strong>Harga jual (Rp)</strong><strong></strong></div>
<div id="itemRows" data-item-list="penjualan">
<?php for($i=0;$i<3;$i++):?><div class="form-row item-row"><label>Barang<select name="idbarang[]" class="product-pick"><option value="">Pilih barang</option><?php foreach($products as $b):?><option value="<?= (int)$b['idbarang'] ?>" data-price="<?= (int)($b['harga_jual']??0) ?>" data-stock="<?= (int)$b['stock'] ?>"><?= viewEsc($b['nama_barang'].' (stok '.$b['stock'].' '.$b['nama_satuan'].')') ?></option><?php endforeach;?></select></label><label>Jumlah<input type="number" name="jumlah[]" min="1" max="100000" value="1"></label><label>Harga (otomatis)<input type="text" class="unit-price" readonly placeholder="Pilih barang"></label><button class="remove-row" type="button">×</button></div><?php endfor;?></div>
<div class="toolbar"><button type="button" class="small-btn" data-add-item>+ Tambah barang</button><span class="form-hint">Perhitungan final dilakukan di database saat menyimpan.</span></div>
<div class="form-actions"><button type="submit" class="primary-btn" <?= $margin===null?'disabled':'' ?>>Simpan Penjualan</button><a href="penjualan.php">Batal</a></div>
</form></section>
<?php akhiriLayout(); ?>
