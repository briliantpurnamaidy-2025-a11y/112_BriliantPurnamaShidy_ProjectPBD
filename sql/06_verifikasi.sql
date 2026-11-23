-- 1. Pastikan database aktif sudah benar
SELECT DATABASE() AS database_aktif;

-- 2. Periksa jumlah View pada skema aktif (target >= 15, tidak harus persis 15)
SELECT TABLE_NAME AS nama_view
FROM INFORMATION_SCHEMA.VIEWS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- 3. Periksa objek yang baru dibuat
SHOW CREATE PROCEDURE sp_tampil_barang_per_satuan;
SHOW CREATE FUNCTION fn_hitung_harga_jual;
SHOW TRIGGERS LIKE 'detail_penerimaan';

-- 4. Tes fungsi dan SP
CALL sp_tampil_barang_per_satuan(3);
SELECT fn_hitung_harga_jual(18000, 20) AS hasil_contoh;

-- 5. View transaksi, mungkin belum ada data
SELECT * FROM v_pengadaan;
SELECT * FROM v_detail_pengadaan;
SELECT * FROM v_penerimaan;
SELECT * FROM v_detail_penerimaan;
SELECT * FROM v_penjualan;
SELECT * FROM v_detail_penjualan;
SELECT * FROM v_retur;
SELECT * FROM v_detail_retur;
SELECT * FROM v_kartu_stok;
