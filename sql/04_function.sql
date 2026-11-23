-- Function menghitung harga simulasi dengan metode markup, bukan margin laba bersih.
-- Tidak membaca tabel; perhitungan hanya memakai argumen input.
DROP FUNCTION IF EXISTS fn_hitung_harga_jual;

CREATE FUNCTION fn_hitung_harga_jual(
    p_harga DECIMAL(12,2),
    p_markup DECIMAL(5,2)
)
RETURNS DECIMAL(14,2)
DETERMINISTIC
NO SQL
RETURN ROUND(p_harga * (1 + p_markup / 100), 2);

-- Hasil contoh: 21600.00
SELECT fn_hitung_harga_jual(18000, 20) AS hasil_simulasi;
