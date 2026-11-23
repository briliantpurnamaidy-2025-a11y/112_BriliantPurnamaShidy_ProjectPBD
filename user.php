<?php
declare(strict_types=1);
require_once __DIR__.'/includes/ops.php';
require_once __DIR__.'/includes/layout.php';
$user=requireAccess('user');$pdo=db();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
    requireAccess('user','write'); verifyAppCsrf();
    $action=(string)($_POST['action']??'');
    $id=$action==='create'?null:idPos($_POST['id']??'','User');
    if(in_array($action,['create','update'],true)){
        $username=trim((string)($_POST['username']??''));
        $role=idPos($_POST['idrole']??'','Role');
        $password=(string)($_POST['password']??'');
        if(!preg_match('/^[a-zA-Z0-9_]{3,45}$/D',$username)||!in_array($role,[1,3,4],true)) throw new RuntimeException('Username atau role tidak valid.');
        if($action==='create'){
            if(strlen($password)<10) throw new RuntimeException('Password baru minimal 10 karakter.');
            $q=$pdo->prepare('INSERT INTO `user`(username,password,idrole) VALUES (?,?,?)');
            $q->execute([$username,password_hash($password,PASSWORD_BCRYPT),$role]);
        }else{
            if($id===$user['iduser']&&$role!==1)throw new RuntimeException('Administrator tidak boleh menurunkan hak akses akunnya sendiri.');
            if($password!==''&&strlen($password)<10)throw new RuntimeException('Password baru minimal 10 karakter.');
            $sql=$password!==''?'UPDATE `user` SET username=?,idrole=?,password=? WHERE iduser=?':'UPDATE `user` SET username=?,idrole=? WHERE iduser=?';
            $args=$password!==''?[$username,$role,password_hash($password,PASSWORD_BCRYPT),$id]:[$username,$role,$id];
            $q=$pdo->prepare($sql);$q->execute($args);
        }
    }elseif($action==='delete'){
        if($id===$user['iduser'])throw new RuntimeException('Tidak bisa menghapus akun yang sedang dipakai.');
        $q=$pdo->prepare('DELETE FROM `user` WHERE iduser=?');$q->execute([$id]);
    }else throw new RuntimeException('Aksi tidak dikenal.');
    flashSet('Pengguna berhasil diperbarui.');redirectTo('user.php');
 }catch(Throwable $e){$error=$e instanceof PDOException&&$e->getCode()==='23000'?'Username sudah digunakan atau akun punya riwayat transaksi.':dbError($e);}
}
$edit=null;
if(isset($_GET['edit'])&&roleCan('user','write')){
    $q=$pdo->prepare('SELECT iduser,username,idrole FROM `user` WHERE iduser=?');$q->execute([idPos($_GET['edit'])]);$edit=$q->fetch();
}
$users=[];
try{$users=$pdo->query('SELECT iduser,username,nama_role,idrole FROM v_master_user ORDER BY iduser')->fetchAll();}catch(Throwable $e){$error=dbError($e);}
mulaiLayout('Data Pengguna','user');
?>
<div class="page-header"><div><a class="breadcrumb-back" href="index.php">← Dashboard</a><h1>Data Pengguna<span class="accent">.</span></h1><p>Kelola akun dan peran pengguna aplikasi.</p></div></div>
<?php if($f=flashTake()):?><div class="notice notice-info"><?= viewEsc($f['message']) ?></div><?php endif;?><?php if($error):?><div class="notice notice-error"><?= viewEsc($error) ?></div><?php endif;?>
<?php if(roleCan('user','write')):?><section class="panel"><div class="panel-heading"><h2><?= $edit?'Edit':'Tambah' ?> Pengguna</h2></div><form class="master-form" method="post"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="<?= $edit?'update':'create' ?>"><?php if($edit):?><input type="hidden" name="id" value="<?= (int)$edit['iduser'] ?>"><?php endif;?>
<label>Username<input name="username" maxlength="45" required value="<?= viewEsc($edit['username']??'') ?>" pattern="[A-Za-z0-9_]{3,45}"></label>
<label>Role<select name="idrole"><?php foreach([1=>'Administrator',3=>'Kasir',4=>'Manajer'] as $id=>$label):?><option value="<?= $id ?>" <?= (int)($edit['idrole']??3)===$id?'selected':'' ?>><?= viewEsc($label) ?></option><?php endforeach;?></select></label>
<label><?= $edit?'Password baru (kosongkan bila tidak diubah)':'Password awal (minimal 10 karakter)' ?><input name="password" type="password" autocomplete="new-password" <?= !$edit?'required':'' ?> minlength="10"></label>
<div class="form-actions"><button class="primary-btn"><?= $edit?'Simpan Akun':'Tambah Akun' ?></button><?php if($edit):?><a href="user.php">Batal Edit</a><?php endif;?></div></form></section><?php endif;?>
<section class="panel section-spacing"><div class="panel-heading"><h2>Daftar Akun</h2></div><div class="table-wrap"><table class="data-table"><thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Akses</th><?php if(roleCan('user','write')):?><th>Aksi</th><?php endif;?></tr></thead><tbody><?php foreach($users as $u):?><tr><td><?= (int)$u['iduser'] ?></td><td><?= viewEsc($u['username']) ?></td><td><?= viewEsc($u['nama_role']) ?></td><td><?= (int)$u['idrole']===2?'Tidak diberikan':'Aktif' ?></td><?php if(roleCan('user','write')):?><td><a href="user.php?edit=<?= (int)$u['iduser'] ?>">Edit</a><?php if((int)$u['iduser']!==$user['iduser']):?><form method="post" class="inline-form" onsubmit="return confirm('Hapus pengguna ini jika belum memiliki transaksi?')"><input type="hidden" name="csrf" value="<?= viewEsc(appCsrf()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['iduser'] ?>"><button>Hapus</button></form><?php endif;?></td><?php endif;?></tr><?php endforeach;?></tbody></table></div></section>
<?php akhiriLayout(); ?>
