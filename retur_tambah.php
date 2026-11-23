<?php
declare(strict_types=1);
require_once __DIR__.'/includes/ops.php';
require_once __DIR__.'/includes/layout.php';
$user=requireAccess('retur','write');$pdo=db();$error='';
$details=$pdo->query('SELECT d.iddetail_penerimaan,d.jumlah_terima,d.harga_satuan_terima,d.idpenerimaan,d.barang_idbarang,b.nama AS nama_barang FROM detail_penerimaan d JOIN barang b ON b.idbarang=d.barang_idbarang ORDER BY d.iddetail_penerimaan DESC')->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
    verifyAppCsrf();$detailId=idPos($_POST['iddetail_penerimaan']??'','Detail penerimaan');
    $qty=integerAmount($_POST['jumlah']??'','Jumlah retur',1,100000);
    $reason=trim((string)($_POST['alasan']??''));
    if($reason===''||strlen($reason)>200)throw new RuntimeException('Isi alasan retur maksimal 200 karakter.');
    $pdo->beginTransaction();
    $q=$pdo->prepare('SELECT d.iddetail_penerimaan,d.idpenerimaan,d.barang_idbarang,d.jumlah_terima FROM detail_penerimaan d WHERE d.iddetail_penerimaan=? FOR UPDATE');
    $q->execute([$detailId]);$row=$q->fetch();if(!$row)throw new RuntimeException('Detail penerimaan tidak ditemukan.');
    $q=$pdo->prepare('SELECT COALESCE(SUM(jumlah),0) FROM detail_retur WHERE iddetail_penerimaan=?');
    $q->execute([$detailId]);$already=(int)$q->fetchColumn();
    if($qty>(int)$row['jumlah_terima']-$already)throw new RuntimeException('Jumlah retur melebihi sisa barang dari penerimaan.');
    $q=$pdo->prepare('SELECT idbarang FROM barang WHERE idbarang=? FOR UPDATE');$q->execute([$row['barang_idbarang']]);$q->fetchColumn();
    $q=$pdo->prepare('INSERT INTO retur(idpenerimaan,iduser) VALUES (?,?)');$q->execute([$row['idpenerimaan'],$user['iduser']]);$returId=(int)$pdo->lastInsertId();
    $q=$pdo->prepare('INSERT INTO detail_retur(idretur,iddetail_penerimaan,jumlah,alasan) VALUES (?,?,?,?)');$q->execute([$returId,$detailId,$qty,$reason]);
    $q=$pdo->prepare("SELECT COUNT(*) FROM kartu_stok WHERE jenis_transaksi='R' AND idtransaksi=?");$q->execute([$returId]);
    if((int)$q->fetchColumn()!==1)throw new RuntimeException('Trigger retur belum mencatat stok. Semua perubahan dibatalkan.');
    $pdo->commit();flashSet('Retur #'.$returId.' berhasil; stok berkurang.');redirectTo('retur.php');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=dbError($e);}
}
mulaiLayout('Retur ke Vendor','retur');
?>
<div class="page-header"><div><a class="breadcrumb-back" href="retur.php">← Daftar Retur</a><h1>Retur ke Vendor<span class="accent">.</span></h1><p>Pengembalian barang dari penerimaan kepada vendor (fitur tambahan).</p></div></div>
<?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif;?>
<section class="panel"><form class="master-form" method="post"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><label>Barang dari penerimaan<select name="iddetail_penerimaan" required><option value="">Pilih penerimaan</option><?php foreach($details as $d):?><option value="<?= (int)$d['iddetail_penerimaan'] ?>">#<?= (int)$d['idpenerimaan'] ?> - <?= viewEsc($d['nama_barang']) ?> (<?= (int)$d['jumlah_terima'] ?> unit diterima)</option><?php endforeach;?></select></label><label>Jumlah retur<input type="number" min="1" name="jumlah" required value="1"></label><label>Alasan retur<textarea name="alasan" maxlength="200" required></textarea></label><div class="form-actions"><button class="primary-btn">Simpan Retur</button></div></form></section>
<?php akhiriLayout(); ?>
