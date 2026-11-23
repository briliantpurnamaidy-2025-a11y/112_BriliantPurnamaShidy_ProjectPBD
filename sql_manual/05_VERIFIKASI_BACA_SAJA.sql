-- BOLEH dijalankan kapan pun, tidak mengubah database.
SELECT DATABASE() AS database_aktif;
SELECT TABLE_NAME,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_TYPE,TABLE_NAME;
SELECT ROUTINE_NAME,ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE();
SELECT TRIGGER_NAME,EVENT_OBJECT_TABLE,EVENT_MANIPULATION FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE();
SELECT idrole,nama_role FROM `role`;
SELECT u.iduser,u.username,r.nama_role,CHAR_LENGTH(u.password) AS panjang_sandi FROM `user` u JOIN role r ON r.idrole=u.idrole;
SELECT idmargin_penjualan,persen,status FROM margin_penjualan ORDER BY idmargin_penjualan;
SELECT idbarang,nama,harga,status FROM barang ORDER BY idbarang;
SELECT idpengadaan,status,subtotal_nilai,ppn,total_nilai FROM pengadaan ORDER BY idpengadaan DESC LIMIT 10;
SELECT idpenerimaan,idpengadaan,status FROM penerimaan ORDER BY idpenerimaan DESC LIMIT 10;
SELECT idpenjualan,subtotal_nilai,ppn,total_nilai FROM penjualan ORDER BY idpenjualan DESC LIMIT 10;
SELECT idkartu_stok,jenis_transaksi,idtransaksi,idbarang,masuk,keluar,stock FROM kartu_stok ORDER BY idkartu_stok DESC LIMIT 20;
