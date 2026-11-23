-- HANYA jika perlu. Jalankan secara manual SETELAH backup, jangan otomatis.
-- PDM menyimpan ID HEADER penerimaan/retur BIGINT, kartu_stok.idtransaksi INT.
-- Untuk menghindari overflow saat transaksi makin besar:
ALTER TABLE kartu_stok MODIFY COLUMN idtransaksi BIGINT NOT NULL;

-- UNTUK UAS 10-20 jenis barang yang butuh kode '01'..'20'.
-- Jangan jalankan perubahan ini saat demo jika belum diperlukan:
-- ALTER TABLE barang MODIFY COLUMN jenis VARCHAR(10) NOT NULL;
