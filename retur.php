<?php
declare(strict_types=1);
require_once __DIR__.'/includes/view_page.php';
$aksi=roleCan('retur','write')?['href'=>'retur_tambah.php','title'=>'+ Catat Retur Vendor']:null;
tampilkanHalamanView('retur','SELECT idretur,idpenerimaan,petugas,created_at FROM v_retur ORDER BY idretur DESC',$aksi);
