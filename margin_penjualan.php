<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/ops.php';
require_once __DIR__ . '/includes/layout.php';
$user=requireAccess('margin');
$pdo=db();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        requireAccess('margin','write');verifyAppCsrf();
        $action=(string)($_POST['action']??'');
        $pdo->beginTransaction();
        // Lock seluruh baris agar pergantian margin oleh dua operator tidak bersaing.
        $pdo->query('SELECT idmargin_penjualan FROM margin_penjualan ORDER BY idmargin_penjualan FOR UPDATE')->fetchAll();
        if($action==='activate'){
            $id=idPos($_POST['id']??'','Margin');
            $q=$pdo->prepare('SELECT idmargin_penjualan FROM margin_penjualan WHERE idmargin_penjualan=?');$q->execute([$id]);
            if(!$q->fetch())throw new RuntimeException('Margin tidak ditemukan.');
            $pdo->exec('UPDATE margin_penjualan SET status=0');
            $q=$pdo->prepare('UPDATE margin_penjualan SET status=1,iduser=? WHERE idmargin_penjualan=?');$q->execute([$user['iduser'],$id]);
        } elseif($action==='new'){
            $percent=(string)($_POST['persen']??'');
            if(!is_numeric($percent)||(float)$percent<0||(float)$percent>100)throw new RuntimeException('Persentase harus antara 0 sampai 100.');
            $q=$pdo->prepare('INSERT INTO margin_penjualan(persen,status,iduser) VALUES (?,0,?)');
            $q->execute([(float)$percent,$user['iduser']]);
        } else throw new RuntimeException('Aksi tidak dikenal.');
        $pdo->commit();flashSet('Pengaturan margin berhasil diperbarui.');redirectTo('margin_penjualan.php');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=dbError($e);}
}
$rows=[];
try{$rows=$pdo->query('SELECT idmargin_penjualan,persen,status,petugas,created_at FROM v_margin_semua ORDER BY idmargin_penjualan')->fetchAll();}catch(Throwable $e){$error=dbError($e);}
mulaiLayout('Margin Penjualan','margin');
?>
<div class="page-header"><div><a class="breadcrumb-back" href="index.php">← Dashboard</a><h1>Margin Penjualan<span class="accent">.</span></h1><p>Hanya satu margin aktif yang digunakan untuk transaksi berikutnya.</p></div></div>
<?php if($f=flashTake()):?><div class="notice notice-info"><?= viewEsc($f['message']) ?></div><?php endif;?>
<?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif;?>
<section class="panel"><div class="panel-heading"><h2>Daftar Margin</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Persentase</th><th>Status</th><th>Diatur oleh</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($rows as $row):?><tr><td><?= viewEsc($row['persen']) ?>%</td><td><?= (int)$row['status']===1?'Aktif':'Nonaktif' ?></td><td><?= viewEsc($row['petugas']) ?></td><td><?php if(roleCan('margin','write')&&(int)$row['status']!==1):?><form method="post"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="activate"><input type="hidden" name="id" value="<?= (int)$row['idmargin_penjualan'] ?>"><button class="small-btn" type="submit">Jadikan Aktif</button></form><?php else:?>—<?php endif;?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php if(roleCan('margin','write')):?><section class="panel section-spacing"><div class="panel-heading"><h2>Tambah Persentase Margin</h2></div><form method="post" class="master-form"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="new"><label>Persentase baru (%)<input type="number" min="0" max="100" step="0.01" name="persen" required></label><div class="form-actions"><button class="primary-btn">Tambahkan Margin Nonaktif</button></div></form></section><?php endif;?>
<?php akhiriLayout(); ?>
