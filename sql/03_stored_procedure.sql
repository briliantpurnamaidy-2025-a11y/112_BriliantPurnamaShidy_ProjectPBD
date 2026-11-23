-- SP yang sebelumnya pernah dibuat, kali ini membaca SQL View.
-- Pastikan file 01_perbarui_view_barang.sql sudah dijalankan.
-- Jalankan pernyataan DROP dan CREATE sebagai 2 statement biasa di HeidiSQL.
DROP PROCEDURE IF EXISTS sp_tampil_barang_per_satuan;

CREATE PROCEDURE sp_tampil_barang_per_satuan(IN p_idsatuan INT)
SELECT idbarang, nama_barang, nama_satuan, harga, status
FROM v_master_barang
WHERE idsatuan = p_idsatuan
ORDER BY nama_barang ASC;

-- CONTOH UJI (kilogram = 3 jika data master mengikuti contoh):
CALL sp_tampil_barang_per_satuan(3);
