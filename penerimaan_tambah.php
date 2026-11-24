<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/ops.php';
require_once __DIR__ . '/includes/layout.php';
$user = requireAccess('penerimaan','write');
$pdo=db();
$error='';
$orders=$pdo->query("SELECT idpengadaan,nama_vendor,dibuat_pada FROM v_pengadaan WHERE status='A' ORDER BY idpengadaan DESC")->fetchAll();
$selected = isset($_POST['idpengadaan']) ? (int)($_POST['idpengadaan']??0) : (int)($_GET['idpengadaan']??0);
$lines=[];
function remainingLines(PDO $pdo,int $orderId): array {
    $q=$pdo->prepare('SELECT d.idbarang,b.nama AS nama_barang,d.jumlah,d.harga_satuan, COALESCE((SELECT SUM(x.jumlah_terima) FROM detail_penerimaan x JOIN penerimaan r ON r.idpenerimaan=x.idpenerimaan WHERE r.idpengadaan=d.idpengadaan AND x.barang_idbarang=d.idbarang),0) AS sudah FROM detail_pengadaan d JOIN barang b ON b.idbarang=d.idbarang WHERE d.idpengadaan=? ORDER BY d.idbarang');
    $q->execute([$orderId]); return $q->fetchAll();
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        verifyAppCsrf();
        $orderId=idPos($_POST['idpengadaan']??'', 'Pengadaan');
        $qtyPost=$_POST['jumlah_terima']??null;
        $pricePost=$_POST['harga_satuan_terima']??null;
        if (!is_array($qtyPost)||!is_array($pricePost)) throw new RuntimeException('Rincian penerimaan tidak valid.');
        $pdo->beginTransaction();
        $lock=$pdo->prepare('SELECT idpengadaan,status FROM pengadaan WHERE idpengadaan=? FOR UPDATE');
        $lock->execute([$orderId]); $header=$lock->fetch();
        if (!$header || $header['status']!=='A') throw new RuntimeException('Pengadaan ini tidak tersedia atau sudah selesai.');
        $lines=remainingLines($pdo,$orderId);
        if (!$lines) throw new RuntimeException('Pengadaan tidak mempunyai rincian.');
        $ins=[];$complete=true;
        foreach ($lines as $l) {
            $id=(int)$l['idbarang'];
            $remaining=(int)$l['jumlah']-(int)$l['sudah'];
            if ($remaining<0) throw new RuntimeException('Data penerimaan lama melebihi jumlah pesanan.');
            $qty=integerAmount($qtyPost[$id]??'0','Jumlah diterima',0,100000);
            $price=integerAmount($pricePost[$id]??(string)$l['harga_satuan'],'Harga terima',0,100000000);
            if ($qty>$remaining) throw new RuntimeException('Penerimaan '.$l['nama_barang'].' melebihi sisa pesanan ('.$remaining.').');
            if ($qty>0) {
                $sub=$qty*$price;
                if ($sub>2000000000) throw new RuntimeException('Nilai penerimaan melewati batas INT.');
                $ins[]=['id'=>$id,'qty'=>$qty,'price'=>$price,'subtotal'=>$sub];
            }
            if ($remaining-$qty>0) $complete=false;
        }
        if (!$ins) throw new RuntimeException('Isi setidaknya satu jumlah barang yang diterima.');
        
        foreach ($ins as $x) {
            $q=$pdo->prepare('SELECT idbarang FROM barang WHERE idbarang=? FOR UPDATE');
            $q->execute([$x['id']]); $q->fetchColumn();
        }
        $q=$pdo->prepare("INSERT INTO penerimaan (idpengadaan,iduser,status) VALUES (?,?,'A')");
        $q->execute([$orderId,$user['iduser']]); $receiptId=(int)$pdo->lastInsertId();
        $q=$pdo->prepare('INSERT INTO detail_penerimaan (idpenerimaan,barang_idbarang,jumlah_terima,harga_satuan_terima,sub_total_terima) VALUES (?,?,?,?,?)');
        foreach($ins as $x) $q->execute([$receiptId,$x['id'],$x['qty'],$x['price'],$x['subtotal']]);
      
        $chk=$pdo->prepare("SELECT COUNT(*) FROM kartu_stok WHERE jenis_transaksi='M' AND idtransaksi=?");
        $chk->execute([$receiptId]);
        if ((int)$chk->fetchColumn()!==count($ins)) throw new RuntimeException('Pencatatan stok masuk tidak sesuai. Transaksi dibatalkan; periksa Trigger.');
        if ($complete) {
            $q=$pdo->prepare("UPDATE pengadaan SET status='S' WHERE idpengadaan=?"); $q->execute([$orderId]);
            $q=$pdo->prepare("UPDATE penerimaan SET status='S' WHERE idpengadaan=?"); $q->execute([$orderId]);
        }
        $pdo->commit();
        flashSet('Penerimaan #'.$receiptId.' berhasil. '.($complete?'Seluruh pesanan sudah selesai.':'Masih ada sisa pengadaan.'));
        redirectTo('penerimaan.php?id='.$receiptId.'#transaksi-'.$receiptId);
    } catch(Throwable $e) {
        if($pdo->inTransaction())$pdo->rollBack();
        $error=dbError($e);
    }
}
if($selected>0){ try{$lines=remainingLines($pdo,$selected);}catch(Throwable $e){$error=dbError($e);} }
mulaiLayout('Catat Penerimaan','penerimaan');
?>
<div class="page-header"><div><a class="breadcrumb-back" href="penerimaan.php">← Daftar Penerimaan</a><h1>Catat Penerimaan<span class="accent">.</span></h1><p>Pilih pengadaan, masukkan jumlah yang datang, dan perbarui harga beli jika berubah.</p></div></div>
<?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif; ?>
<section class="panel"><div class="panel-heading"><h2>Pengadaan yang sedang diproses</h2></div><form method="get" class="filter-bar"><select name="idpengadaan" required><option value="">Pilih pengadaan</option><?php foreach($orders as $o):?><option value="<?= (int)$o['idpengadaan'] ?>" <?= $selected===(int)$o['idpengadaan']?'selected':'' ?>>#<?= (int)$o['idpengadaan'] ?> · <?= viewEsc($o['nama_vendor']) ?></option><?php endforeach;?></select><button class="small-btn">Tampilkan</button></form></section>
<?php if($selected>0&&$lines):?>
<section class="panel section-spacing"><div class="panel-heading"><h2>Barang yang diterima — Pengadaan #<?= $selected ?></h2></div>
<form method="post" class="tx-form">
<input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="idpengadaan" value="<?= $selected ?>">
<div class="table-wrap"><table class="data-table list-table"><thead><tr><th>Barang</th><th>Dipesan</th><th>Sudah diterima</th><th>Sisa</th><th>Terima sekarang</th><th>Harga beli aktual (Rp)</th></tr></thead><tbody>
<?php foreach($lines as $l):$id=(int)$l['idbarang'];$remaining=(int)$l['jumlah']-(int)$l['sudah'];?><tr>
<td><?= viewEsc($l['nama_barang']) ?></td><td><?= (int)$l['jumlah'] ?></td><td><?= (int)$l['sudah'] ?></td><td><strong><?= $remaining ?></strong></td>
<td><input type="number" name="jumlah_terima[<?= $id ?>]" min="0" max="<?= max(0,$remaining) ?>" value="<?= viewEsc($_POST['jumlah_terima'][$id]??'0') ?>" <?= $remaining===0?'readonly':'' ?>></td>
<td><input type="number" name="harga_satuan_terima[<?= $id ?>]" min="0" max="100000000" value="<?= viewEsc($_POST['harga_satuan_terima'][$id]??$l['harga_satuan']) ?>" required></td>
</tr><?php endforeach; ?></tbody></table></div>
<div class="form-actions"><button type="submit" class="primary-btn">Simpan Penerimaan</button><a href="penerimaan.php">Batal</a></div>
<p class="form-hint">Harga beli pada data barang diperbarui mengikuti penerimaan terbaru. Saldo stok dicatat otomatis.</p>
</form></section>
<?php endif; ?>
<?php akhiriLayout(); ?>
