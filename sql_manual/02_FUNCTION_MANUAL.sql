-- JALANKAN MANUAL pada database praktikumpbd setelah BACKUP.
-- Function harga jual dari harga BELI barang + markup margin (bukan gross margin).
DROP FUNCTION IF EXISTS fn_hitung_harga_jual;
CREATE FUNCTION fn_hitung_harga_jual(p_harga DECIMAL(15,2), p_markup DECIMAL(7,2))
RETURNS DECIMAL(17,2) DETERMINISTIC NO SQL
RETURN ROUND(p_harga * (1 + p_markup / 100), 0);

-- Mendukung PPN 12% pengadaan dan PPN 11% penjualan. Output dibulatkan ke Rupiah penuh.
DROP FUNCTION IF EXISTS fn_total_dengan_ppn;
CREATE FUNCTION fn_total_dengan_ppn(p_subtotal DECIMAL(17,2), p_persen_ppn DECIMAL(5,2))
RETURNS DECIMAL(19,2) DETERMINISTIC NO SQL
RETURN ROUND(p_subtotal * (1 + p_persen_ppn / 100), 0);

-- Uji baca:
SELECT fn_hitung_harga_jual(12500,30) AS harga_jual,
       fn_total_dengan_ppn(100000,12) AS total_pengadaan,
       fn_total_dengan_ppn(100000,11) AS total_penjualan;
