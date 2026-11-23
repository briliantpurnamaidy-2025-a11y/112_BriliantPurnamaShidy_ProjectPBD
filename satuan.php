<?php
declare(strict_types=1);
require_once __DIR__.'/includes/master_crud.php';
tampilkanMasterCrud('satuan',[
 'file'=>'satuan.php','title'=>'Data Satuan','table'=>'satuan','pk'=>'idsatuan',
 'viewSql'=>'SELECT idsatuan,nama_satuan,status FROM v_master_satuan',
 'cols'=>['idsatuan'=>'ID','nama_satuan'=>'Nama Satuan','status'=>'Status'],
 'fields'=>['nama_satuan'=>['type'=>'text','label'=>'Nama Satuan','max'=>45], 'status'=>['type'=>'status','label'=>'Status']],
]);
