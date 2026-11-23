-- Jalankan langkah berurutan dalam SATU koneksi HeidiSQL.
-- Jangan eksekusi keseluruhan sekaligus jika ingin screenshot hasil sebelum ROLLBACK.
-- Pastikan akun iduser=1, vendor=1, barang=1 tersedia.
-- Jika ada transaksi lain, jangan gunakan script ini sebagai skrip produksi.

START TRANSACTION;

INSERT INTO pengadaan (user_iduser, vendor_idvendor)
VALUES (1, 1);
SET @uji_id_pengadaan = LAST_INSERT_ID();

INSERT INTO penerimaan (idpengadaan, iduser)
VALUES (@uji_id_pengadaan, 1);
SET @uji_id_penerimaan = LAST_INSERT_ID();

INSERT INTO detail_penerimaan
  (idpenerimaan, barang_idbarang, jumlah_terima,
   harga_satuan_terima, sub_total_terima)
VALUES (@uji_id_penerimaan, 1, 10, 18000, 180000);

-- Bukti trigger: data di kartu_stok dihasilkan otomatis, BUKAN manual.
SELECT * FROM v_kartu_stok
WHERE idtransaksi = @uji_id_penerimaan
  AND idbarang = 1 AND jenis_transaksi = 'M'
ORDER BY idkartu_stok DESC;

-- Ambil screenshot di sini. Nilai saldo = stok sebelumnya + 10.
-- Ketik ROLLBACK setelah selesai agar data uji batal:
-- ROLLBACK;
