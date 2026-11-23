<?php
declare(strict_types=1);
require_once __DIR__.'/includes/master_crud.php';
tampilkanMasterCrud('vendor',[
 'file'=>'vendor.php','title'=>'Data Vendor','table'=>'vendor','pk'=>'idvendor',
 'viewSql'=>'SELECT idvendor,nama_vendor,badan_hukum,status FROM v_master_vendor',
 'cols'=>['idvendor'=>'ID','nama_vendor'=>'Nama Vendor','badan_hukum'=>'Badan Hukum (1=PT, 0=lainnya)','status'=>'Status'],
 'fields'=>['nama_vendor'=>['type'=>'text','label'=>'Nama Vendor','max'=>100], 'badan_hukum'=>['type'=>'text','label'=>'Badan Hukum: 1=PT, 0=CV/UD','max'=>1], 'status'=>['type'=>'status','label'=>'Status']],
]);
