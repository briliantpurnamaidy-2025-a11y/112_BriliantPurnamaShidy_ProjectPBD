<?php
declare(strict_types=1);
require_once __DIR__.'/includes/ops.php';
require_once __DIR__.'/includes/layout.php';
requireAccess('penerimaan','write');$pdo=db();$error='';
$id=idPos($_GET['id']??$_POST['id']??'', 'Detail penerimaan');
$q=$pdo->prepare('SELECT d.iddetail_penerimaan,d.idpenerimaan,d.barang_idbarang,d.jumlah_terima,d.harga_satuan_terima,b.nama FROM detail_penerimaan d JOIN barang b ON b.idbarang=d.barang_idbarang WHERE d.iddetail_penerimaan=?');
$q->execute([$id]);$detail=$q->fetch();if(!$detail){http_response_code(404);exit('Rincian penerimaan tidak ditemukan.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        verifyAppCsrf();$price=integerAmount($_POST['harga']??'','Harga beli baru',0,100000000);
        $subtotal=$price*(int)$detail['jumlah_terima'];if($subtotal>2000000000)throw new RuntimeException('Nilai terlalu besar.');
        $pdo->beginTransaction();
        $q=$pdo->prepare('SELECT idbarang FROM barang WHERE idbarang=? FOR UPDATE');$q->execute([(int)$detail['barang_idbarang']]);$q->fetchColumn();
        $q=$pdo->prepare('UPDATE detail_penerimaan SET harga_satuan_terima=?,sub_total_terima=? WHERE iddetail_penerimaan=?');
        $q->execute([$price,$subtotal,$id]);
    
        $q=$pdo->prepare('SELECT harga_satuan_terima FROM detail_penerimaan WHERE barang_idbarang=? ORDER BY iddetail_penerimaan DESC LIMIT 1');
        $q->execute([(int)$detail['barang_idbarang']]);$latest=(int)$q->fetchColumn();
        $q=$pdo->prepare('UPDATE barang SET harga=? WHERE idbarang=?');$q->execute([$latest,(int)$detail['barang_idbarang']]);
        $pdo->commit();flashSet('Harga penerimaan berhasil diperbarui; stok tetap sama.');redirectTo('penerimaan.php?id='.(int)$detail['idpenerimaan'].'#transaksi-'.(int)$detail['idpenerimaan']);
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=dbError($e);}
}
mulaiLayout('Edit Harga Penerimaan','penerimaan');
?>
<div class="page-header"><div><a class="breadcrumb-back" href="penerimaan.php?id=<?= (int)$detail['idpenerimaan'] ?>#transaksi-<?= (int)$detail['idpenerimaan'] ?>">← Penerimaan</a><h1>Edit Harga Beli<span class="accent">.</span></h1><p>Jumlah barang yang sudah diterima tidak diubah agar kartu stok tetap konsisten.</p></div></div>
<?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif;?>
<section class="panel"><form method="post" class="master-form"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="id" value="<?= $id ?>"><label>Barang<input disabled value="<?= viewEsc($detail['nama']) ?>"></label><label>Jumlah diterima<input disabled value="<?= (int)$detail['jumlah_terima'] ?>"></label><label>Harga satuan baru (Rp)<input type="number" name="harga" min="0" max="100000000" value="<?= (int)$detail['harga_satuan_terima'] ?>" required></label><div class="form-actions"><button class="primary-btn">Simpan Harga</button></div></form></section>
<?php akhiriLayout(); ?>
