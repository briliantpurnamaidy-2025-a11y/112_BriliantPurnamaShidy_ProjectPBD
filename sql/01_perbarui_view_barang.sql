-- Jalankan lebih dahulu: Stored Procedure membutuhkan kolom idsatuan dari View ini.
-- Tidak mengubah tabel barang atau data barang.
CREATE OR REPLACE VIEW v_master_barang AS
SELECT b.idbarang, b.jenis, b.nama AS nama_barang, b.idsatuan,
       s.nama_satuan, b.harga, b.status
FROM barang AS b
JOIN satuan AS s ON s.idsatuan = b.idsatuan;

SELECT * FROM v_master_barang;
