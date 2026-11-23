-- JALANKAN MANUAL setelah 01_SEMUA_VIEW_MANUAL.sql; procedure hanya SELECT.
DROP PROCEDURE IF EXISTS sp_tampil_barang_per_satuan;
CREATE PROCEDURE sp_tampil_barang_per_satuan(IN p_idsatuan INT)
SELECT idbarang,nama_barang,nama_satuan,harga,status
FROM v_master_barang WHERE idsatuan=p_idsatuan ORDER BY nama_barang;

DROP PROCEDURE IF EXISTS sp_riwayat_stok_per_barang;
CREATE PROCEDURE sp_riwayat_stok_per_barang(IN p_idbarang INT)
SELECT idkartu_stok,nama_barang,jenis_transaksi,masuk,keluar,stock,created_at,idtransaksi,idbarang
FROM v_kartu_stok WHERE idbarang=p_idbarang ORDER BY idkartu_stok DESC;
