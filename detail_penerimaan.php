<?php
declare(strict_types=1);
require_once __DIR__.'/includes/ops.php';
require_once __DIR__.'/includes/layout.php';
requireAccess('detail_penerimaan');$pdo=db();$rows=[];$error='';
try{$rows=$pdo->query('SELECT iddetail_penerimaan,idpenerimaan,nama_barang,jumlah_terima,harga_satuan_terima,sub_total_terima FROM v_detail_penerimaan ORDER BY iddetail_penerimaan DESC')->fetchAll();}catch(Throwable $e){$error=dbError($e);}
mulaiLayout('Detail Penerimaan','detail_penerimaan');
?>
<div class="page-header"><div><a class="breadcrumb-back" href="penerimaan.php">← Daftar Penerimaan</a><h1>Detail Penerimaan<span class="accent">.</span></h1><p>Harga beli aktual dapat dikoreksi tanpa mengubah jumlah stok yang sudah dicatat.</p></div></div>
<?php if($f=flashTake()):?><div class="notice notice-info"><?= viewEsc($f['message']) ?></div><?php endif;?><?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif;?>
<section class="panel"><div class="table-wrap"><table class="data-table"><thead><tr><th>ID</th><th>Penerimaan</th><th>Barang</th><th>Jumlah</th><th>Harga Beli</th><th>Subtotal</th><?php if(roleCan('penerimaan','write')):?><th>Aksi</th><?php endif;?></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?= (int)$r['iddetail_penerimaan'] ?></td><td>#<?= (int)$r['idpenerimaan'] ?></td><td><?= viewEsc($r['nama_barang']) ?></td><td><?= (int)$r['jumlah_terima'] ?></td><td><?= viewEsc(viewUang($r['harga_satuan_terima'])) ?></td><td><?= viewEsc(viewUang($r['sub_total_terima'])) ?></td><?php if(roleCan('penerimaan','write')):?><td><a href="penerimaan_harga_edit.php?id=<?= (int)$r['iddetail_penerimaan'] ?>">Edit harga</a></td><?php endif;?></tr><?php endforeach;?></tbody></table></div></section>
<?php akhiriLayout(); ?>
