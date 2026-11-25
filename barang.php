<?php
declare(strict_types=1);
require_once __DIR__.'/includes/ops.php';
require_once __DIR__.'/includes/layout.php';
requireAccess('barang');
$pdo=db();$error='';$allowed=roleCan('barang','write');
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        requireAccess('barang','write');verifyAppCsrf();
        $action=(string)($_POST['action']??'');
        $id=$action==='create'?null:idPos($_POST['id']??'','Barang');
        if(in_array($action,['create','update'],true)){
            $nama=trim((string)($_POST['nama']??''));$jenis=trim((string)($_POST['jenis']??''));
            $satuan=idPos($_POST['idsatuan']??'','Satuan');
            $harga=integerAmount($_POST['harga']??'','Harga beli',0,100000000);
            $status=(string)($_POST['status']??'1');
            if($nama===''||strlen($nama)>45||(strlen($jenis)<1||strlen($jenis)>2)||!in_array($status,['0','1'],true))throw new RuntimeException('Nama, kode jenis (1–2 karakter), atau status tidak valid.');
            $q=$pdo->prepare('SELECT idsatuan FROM satuan WHERE idsatuan=?');$q->execute([$satuan]);if(!$q->fetchColumn())throw new RuntimeException('Satuan tidak ditemukan.');
            if($action==='create'){
                $q=$pdo->prepare('INSERT INTO barang (jenis,nama,idsatuan,status,harga) VALUES (?,?,?,?,?)');$q->execute([$jenis,$nama,$satuan,$status,$harga]);
            }else{
                $q=$pdo->prepare('UPDATE barang SET jenis=?,nama=?,idsatuan=?,status=?,harga=? WHERE idbarang=?');$q->execute([$jenis,$nama,$satuan,$status,$harga,$id]);
            }
        }elseif($action==='toggle'){
            $q=$pdo->prepare('UPDATE barang SET status=CASE WHEN status=1 THEN 0 ELSE 1 END WHERE idbarang=?');$q->execute([$id]);
        }elseif($action==='delete'){
            $q=$pdo->prepare('DELETE FROM barang WHERE idbarang=?');$q->execute([$id]);
        }else throw new RuntimeException('Aksi tidak dikenal.');
        flashSet('Data barang berhasil diperbarui.');redirectTo('barang.php');
    }catch(Throwable $e){$error=($e instanceof PDOException && $e->getCode()==='23000')?'Barang sudah dipakai transaksi. Silakan nonaktifkan.':dbError($e);}
}
$edit=null;
if(isset($_GET['edit'])&&$allowed){try{$q=$pdo->prepare('SELECT * FROM barang WHERE idbarang=?');$q->execute([idPos($_GET['edit'])]);$edit=$q->fetch();}catch(Throwable $e){$error=dbError($e);}}
$satuan=[];$barang=[];$margin=null;$filter=null;$status=(string)($_GET['status']??'all');
if(!in_array($status,['all','0','1'],true))$status='all';
try{
    $satuan=$pdo->query('SELECT idsatuan,nama_satuan FROM v_master_satuan ORDER BY nama_satuan')->fetchAll();
    $raw=$_GET['idsatuan']??'';
    if($raw!==''&&(!is_string($raw)||!ctype_digit($raw)))throw new RuntimeException('Filter satuan tidak valid.');
    $filter=$raw!==''?(int)$raw:null;
    if($filter!==null){

        $q=$pdo->prepare('CALL sp_tampil_barang_per_satuan(?)');$q->execute([$filter]);$barang=$q->fetchAll();$q->closeCursor();
    }else{
        $barang=$pdo->query('SELECT idbarang,nama_barang,nama_satuan,harga,status FROM v_master_barang ORDER BY nama_barang')->fetchAll();
    }
    if($status!=='all')$barang=array_values(array_filter($barang,static fn($r)=>(string)$r['status']===$status));

  
    $stokRows=$pdo->query(
        'SELECT k.idbarang, k.stock
         FROM v_kartu_stok k
         JOIN (
             SELECT idbarang, MAX(idkartu_stok) AS id_terakhir
             FROM v_kartu_stok
             GROUP BY idbarang
         ) terbaru ON terbaru.idbarang=k.idbarang AND terbaru.id_terakhir=k.idkartu_stok'
    )->fetchAll();
    $stokPerBarang=[];
    foreach($stokRows as $row){$stokPerBarang[(int)$row['idbarang']]=(int)$row['stock'];}
    foreach($barang as &$b){$b['stok_tersedia']=$stokPerBarang[(int)$b['idbarang']]??0;}
    unset($b);

    $ms=$pdo->query('SELECT persen FROM v_margin_aktif')->fetchAll(PDO::FETCH_COLUMN);
    if(count($ms)===1){$margin=(float)$ms[0];$calc=$pdo->prepare('SELECT fn_hitung_harga_jual(?,?)');
        foreach($barang as &$b){$calc->execute([(int)$b['harga'],$margin]);$b['harga_jual']=(int)round((float)$calc->fetchColumn());}unset($b);
    }
}catch(Throwable $e){$error=dbError($e);}
mulaiLayout('Data Barang','barang');
?>

<style>
.row-buttons .barang-toggle-form { display:inline-flex; vertical-align:middle; align-items:center; margin:2px 6px; }
.row-buttons .barang-toggle-form .barang-toggle-switch {
  position:relative; display:inline-block; box-sizing:border-box;
  width:50px; height:28px; min-width:50px; padding:0; margin:0;
  border:1px solid #cbd5e1; border-radius:999px;
  background:#e2e8f0; color:inherit; cursor:pointer;
  vertical-align:middle; appearance:none; -webkit-appearance:none;
  transition:background-color .22s ease,border-color .22s ease,box-shadow .22s ease;
}
.row-buttons .barang-toggle-form .barang-toggle-switch[aria-checked="true"] {
  background:#0085ff; border-color:#0085ff;
}
.row-buttons .barang-toggle-form .barang-toggle-switch .barang-toggle-thumb {
  position:absolute; top:3px; left:3px;
  display:block; width:20px; height:20px; border-radius:50%;
  background:white; box-shadow:0 1px 3px rgba(15,23,42,.22);
  transition:transform .22s ease;
}
.row-buttons .barang-toggle-form .barang-toggle-switch[aria-checked="true"] .barang-toggle-thumb {
  transform:translateX(22px);
}
.row-buttons .barang-toggle-form .barang-toggle-switch:focus-visible {
  outline:3px solid rgba(0,133,255,.28); outline-offset:3px;
}
.row-buttons .barang-toggle-form .barang-toggle-switch:hover {
  box-shadow:0 0 0 3px rgba(0,133,255,.10);
}
.row-buttons .barang-toggle-form .barang-toggle-switch:disabled { cursor:wait; opacity:.84; }
@media (prefers-reduced-motion: reduce) {
  .row-buttons .barang-toggle-form .barang-toggle-switch,
  .row-buttons .barang-toggle-form .barang-toggle-thumb { transition:none; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form.barang-toggle-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (form.dataset.submitting === '1') return;
      var button = form.querySelector('.barang-toggle-switch');
      if (!button) return;
      form.dataset.submitting = '1';
      var nowActive = button.getAttribute('aria-checked') === 'true';
 
      button.setAttribute('aria-checked', nowActive ? 'false' : 'true');
      button.disabled = true;
      var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      setTimeout(function () {
        HTMLFormElement.prototype.submit.call(form);
      }, reduceMotion ? 0 : 230);
    });
  });
});
</script>
<div class="page-header"><div><a class="breadcrumb-back" href="index.php">← Dashboard</a><h1>Data Barang<span class="accent">.</span></h1><p>Harga beli diperbarui saat penerimaan, harga jual mengikuti margin aktif.</p></div></div>
<?php if($f=flashTake()):?><div class="notice notice-info"><?= viewEsc($f['message']) ?></div><?php endif;?>
<?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif;?>
<?php if($allowed):?><section class="panel"><div class="panel-heading"><h2><?= $edit?'Edit':'Tambah' ?> Barang</h2></div><form class="master-form" method="post">
<input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="<?= $edit?'update':'create' ?>"><?php if($edit):?><input type="hidden" name="id" value="<?= (int)$edit['idbarang'] ?>"><?php endif;?>
<label>Nama barang<input name="nama" maxlength="45" required value="<?= viewEsc($edit['nama']??'') ?>"></label>
<label>Kode jenis (1–2 karakter; perlu migrasi untuk 2)<input name="jenis" maxlength="2" required value="<?= viewEsc($edit['jenis']??'1') ?>"></label>
<label>Satuan<select name="idsatuan" required><?php foreach($satuan as $s):?><option value="<?= (int)$s['idsatuan'] ?>" <?= (string)($edit['idsatuan']??'')===(string)$s['idsatuan']?'selected':'' ?>><?= viewEsc($s['nama_satuan']) ?></option><?php endforeach;?></select></label>
<label>Harga beli / dasar (Rp)<input type="number" min="0" max="100000000" name="harga" required value="<?= viewEsc($edit['harga']??'0') ?>"></label>
<label>Status<select name="status"><option value="1" <?= (string)($edit['status']??1)==='1'?'selected':'' ?>>Aktif</option><option value="0" <?= (string)($edit['status']??1)==='0'?'selected':'' ?>>Nonaktif</option></select></label>
<div class="form-actions"><button class="primary-btn"><?= $edit?'Simpan Perubahan':'Tambah Barang' ?></button><?php if($edit):?><a href="barang.php">Batal Edit</a><?php endif;?></div></form></section><?php endif;?>
<section class="panel section-spacing"><div class="panel-heading"><h2>Daftar Barang</h2></div>
<form method="get" class="filter-bar"><label>Filter satuan<select name="idsatuan"><option value="">Semua Satuan</option><?php foreach($satuan as $s):?><option value="<?= (int)$s['idsatuan'] ?>" <?= $filter===(int)$s['idsatuan']?'selected':'' ?>><?= viewEsc($s['nama_satuan']) ?></option><?php endforeach;?></select></label><label>Status<select name="status"><option value="all">Semua</option><option value="1" <?= $status==='1'?'selected':'' ?>>Aktif</option><option value="0" <?= $status==='0'?'selected':'' ?>>Nonaktif</option></select></label><button class="small-btn">Tampilkan</button></form>
<div class="table-wrap"><table class="data-table list-table"><thead><tr><th>Nama Barang</th><th>Satuan</th><th>Stok</th><th>Harga Beli</th><th>Harga Jual</th><th>Status</th><?php if($allowed):?><th>Aksi</th><?php endif;?></tr></thead><tbody>
<?php foreach($barang as $b):?><tr><td><strong><?= viewEsc($b['nama_barang']) ?></strong></td><td><?= viewEsc($b['nama_satuan']) ?></td><td><strong><?= number_format((int)($b['stok_tersedia'] ?? 0), 0, ',', '.') ?></strong></td><td><?= viewEsc(viewUang($b['harga'])) ?></td><td><?= isset($b['harga_jual'])?viewEsc(viewUang($b['harga_jual'])):'—' ?></td><td><?= viewCell('status',$b['status']) ?></td>
<?php if($allowed):?><td class="row-buttons"><a href="barang.php?edit=<?= (int)$b['idbarang'] ?>">Edit</a><form class="inline-form barang-toggle-form" method="post"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$b['idbarang'] ?>"><?php $barangAktif=(string)$b['status']==='1'; ?><button type="submit" class="barang-toggle-switch" role="switch" aria-checked="<?= $barangAktif?'true':'false' ?>" aria-label="<?= viewEsc(($barangAktif?'Nonaktifkan ':'Aktifkan ').$b['nama_barang']) ?>" title="<?= $barangAktif?'Klik untuk menonaktifkan':'Klik untuk mengaktifkan' ?>"><span class="barang-toggle-thumb" aria-hidden="true"></span></button></form><form class="inline-form" method="post" onsubmit="return confirm('Hapus barang ini permanen jika belum digunakan transaksi?')"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$b['idbarang'] ?>"><button>Hapus</button></form></td><?php endif;?></tr><?php endforeach;?></tbody></table></div>
<?php if(!$barang):?><div class="empty-state">Belum ada barang sesuai filter.</div><?php endif;?></section>
<?php akhiriLayout(); ?>
