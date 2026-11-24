<?php
declare(strict_types=1);
require_once __DIR__ . '/ops.php';
require_once __DIR__ . '/layout.php';


function tampilkanMasterCrud(string $key, array $cfg): void
{
    requireAccess($key);
    $pdo=db(); $error=''; $table=$cfg['table']; $pk=$cfg['pk'];
    $allowed=roleCan($key,'write');
    if($_SERVER['REQUEST_METHOD']==='POST'){
        try {
            requireAccess($key,'write');verifyAppCsrf();
            $action=(string)($_POST['action']??'');
            $id=($action==='create')?null:idPos($_POST['id']??'','ID');
            if(in_array($action,['create','update'],true)){
                $vals=[];
                foreach($cfg['fields'] as $field=>$meta){
                    $raw=trim((string)($_POST[$field]??''));
                    if($meta['type']==='text'){
                        if($raw===''||strlen($raw)>$meta['max'])throw new RuntimeException('Isi '.$meta['label'].' (maksimal '.$meta['max'].' karakter).');
                        $vals[$field]=$raw;
                    }elseif($meta['type']==='int'){
                        $vals[$field]=integerAmount($raw,$meta['label'],$meta['min']??0,$meta['max']??100000000);
                    }elseif($meta['type']==='status'){
                        if(!in_array($raw,['0','1'],true))throw new RuntimeException('Status tidak valid.');
                        $vals[$field]=$raw;
                    }else throw new RuntimeException('Konfigurasi field tidak dikenal.');
                }
                if($table==='barang'){
                    if(strlen((string)$vals['jenis'])!==1)throw new RuntimeException('Jenis barang di PDM saat ini hanya 1 karakter (CHAR(1)).');
                    $q=$pdo->prepare('SELECT status FROM satuan WHERE idsatuan=?');$q->execute([$vals['idsatuan']]);
                    if($q->fetchColumn()===false)throw new RuntimeException('Satuan tidak ditemukan.');
                }
                if($table==='vendor'&&!in_array($vals['badan_hukum'],['0','1'],true))throw new RuntimeException('Kode badan hukum harus 0 atau 1.');
                $fields=array_keys($vals);
                if($action==='create'){
                    $sql='INSERT INTO `'.$table.'` (`'.implode('`,`',$fields).'`) VALUES ('.implode(',',array_fill(0,count($fields),'?')).')';
                    $q=$pdo->prepare($sql);$q->execute(array_values($vals));
                }else{
                    $sql='UPDATE `'.$table.'` SET '.implode(',',array_map(static fn($f)=>'`'.$f.'`=?',$fields)).' WHERE `'.$pk.'`=?';
                    $q=$pdo->prepare($sql);$q->execute([...array_values($vals),$id]);
                }
            }elseif($action==='toggle'){
                $q=$pdo->prepare('UPDATE `'.$table.'` SET status=CASE WHEN status=1 THEN 0 ELSE 1 END WHERE `'.$pk.'`=?');$q->execute([$id]);
            }elseif($action==='delete'){
                $q=$pdo->prepare('DELETE FROM `'.$table.'` WHERE `'.$pk.'`=?');$q->execute([$id]);
            }else throw new RuntimeException('Aksi tidak dikenal.');
            flashSet('Data berhasil diperbarui.');redirectTo($cfg['file']);
        }catch(Throwable $e){
            if($e instanceof PDOException && $e->getCode()==='23000') $error='Data masih digunakan dalam transaksi atau data lain. Gunakan tombol Nonaktifkan.';
            else $error=dbError($e);
        }
    }
    $current=null;
    if(isset($_GET['edit'])&&$allowed){
        try{$id=idPos($_GET['edit']);$q=$pdo->prepare('SELECT * FROM `'.$table.'` WHERE `'.$pk.'`=?');$q->execute([$id]);$current=$q->fetch();}
        catch(Throwable $e){$error=dbError($e);}
    }
    $status=(string)($_GET['status']??'all');if(!in_array($status,['all','0','1'],true))$status='all';
    $rows=[];
    try{
        $sql=$cfg['viewSql'];
        if($status!=='all')$sql.=' WHERE status = '.($status==='1'?'1':'0');
        $sql.=' ORDER BY `'.$pk.'` DESC';
        $rows=$pdo->query($sql)->fetchAll();
    }catch(Throwable $e){$error=dbError($e);}
    mulaiLayout($cfg['title'],$key);
    ?>
    <div class="page-header"><div><a class="breadcrumb-back" href="index.php">← Dashboard</a><h1><?= viewEsc($cfg['title']) ?><span class="accent">.</span></h1><p>Kelola informasi dan status data toko.</p></div></div>
    <?php if($f=flashTake()):?><div class="notice notice-info"><?= viewEsc($f['message']) ?></div><?php endif;?>
    <?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif;?>
    <?php if($allowed):?>
    <section class="panel section-spacing"><div class="panel-heading"><h2><?= $current?'Edit':'Tambah' ?> <?= viewEsc($cfg['title']) ?></h2></div>
    <form method="post" class="master-form">
    <input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="<?= $current?'update':'create' ?>">
    <?php if($current):?><input type="hidden" name="id" value="<?= (int)$current[$pk] ?>"><?php endif;?>
    <?php foreach($cfg['fields'] as $field=>$meta):?>
    <label><?= viewEsc($meta['label']) ?>
    <?php if($meta['type']==='status'):?><select name="<?= viewEsc($field) ?>"><option value="1" <?= (string)($current[$field]??'1')==='1'?'selected':'' ?>>Aktif</option><option value="0" <?= (string)($current[$field]??'1')==='0'?'selected':'' ?>>Nonaktif</option></select>
    <?php elseif($field==='idsatuan'):?><select name="idsatuan" required><?php foreach($pdo->query('SELECT idsatuan,nama_satuan FROM v_master_satuan ORDER BY nama_satuan') as $s):?><option value="<?= (int)$s['idsatuan'] ?>" <?= (string)($current['idsatuan']??'')===(string)$s['idsatuan']?'selected':'' ?>><?= viewEsc($s['nama_satuan']) ?></option><?php endforeach;?></select>
    <?php else:?><input name="<?= viewEsc($field) ?>" <?= $meta['type']==='int'?'type="number" min="0"':'type="text"' ?> maxlength="<?= (int)($meta['max']??100000000) ?>" value="<?= viewEsc($current[$field]??'') ?>" required><?php endif; ?>
    </label><?php endforeach;?>
    <div class="form-actions"><button class="primary-btn" type="submit"><?= $current?'Simpan Perubahan':'Tambah Data' ?></button><?php if($current):?><a href="<?= viewEsc($cfg['file']) ?>">Batal Edit</a><?php endif;?></div>
    </form></section><?php endif;?>
    <section class="panel section-spacing"><div class="panel-heading"><h2>Daftar <?= viewEsc($cfg['title']) ?></h2></div>
    <form method="get" class="filter-bar"><label for="filterStatus">Status:</label><select name="status" id="filterStatus"><option value="all">Semua</option><option value="1" <?= $status==='1'?'selected':'' ?>>Aktif</option><option value="0" <?= $status==='0'?'selected':'' ?>>Nonaktif</option></select><button class="small-btn">Tampilkan</button></form>
    <div class="table-wrap"><table class="data-table list-table"><thead><tr><?php foreach($cfg['cols'] as $name=>$label):?><th><?= viewEsc($label) ?></th><?php endforeach; ?><?php if($allowed):?><th>Aksi</th><?php endif;?></tr></thead><tbody>
    <?php foreach($rows as $r):?><tr><?php foreach($cfg['cols'] as $col=>$label):?><td><?= $col==='harga'?viewEsc(viewUang($r[$col]??0)) : ($col==='status'?viewCell('status',$r[$col]??''):viewEsc($r[$col]??'')) ?></td><?php endforeach;?>
    <?php if($allowed):?><td class="row-buttons"><a href="<?= viewEsc($cfg['file']) ?>?edit=<?= (int)$r[$pk] ?>">Edit</a>
    <form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r[$pk] ?>"><button><?= (string)$r['status']==='1'?'Nonaktifkan':'Aktifkan' ?></button></form>
    <form method="post" class="inline-form" onsubmit="return confirm('Hapus permanen hanya jika data belum digunakan. Lanjutkan?')"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r[$pk] ?>"><button>Hapus</button></form></td><?php endif;?></tr><?php endforeach;?></tbody></table></div>
    <?php if(!$rows):?><div class="empty-state">Belum ada data.</div><?php endif;?></section>
    <?php akhiriLayout();
}
