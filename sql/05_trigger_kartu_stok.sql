-- Trigger satu saja: barang masuk setelah INSERT detail_penerimaan.
-- PENTING: Periksa SHOW TRIGGERS LIKE 'detail_penerimaan' sebelumnya.
-- Jika ada trigger stok masuk lain, pastikan tidak terjadi pencatatan ganda.
-- Jalankan sebagai skrip utuh (Run SQL File / Execute all) dengan delimiter $$.
-- Tabel kartu_stok menyimpan riwayat; trigger ini hanya berlaku pada INSERT.

DROP TRIGGER IF EXISTS trg_kartu_stok_masuk;

DELIMITER $$
CREATE TRIGGER trg_kartu_stok_masuk
AFTER INSERT ON detail_penerimaan
FOR EACH ROW
BEGIN
    DECLARE v_kunci INT;
    DECLARE v_stok_akhir INT DEFAULT 0;

    IF NEW.jumlah_terima <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah penerimaan harus positif';
    END IF;

    -- Serialize penambahan stok untuk barang yang sama lewat kunci baris barang.
    SELECT idbarang INTO v_kunci
    FROM barang
    WHERE idbarang = NEW.barang_idbarang
    FOR UPDATE;

    SELECT COALESCE((
        SELECT ks.stock FROM kartu_stok ks
        WHERE ks.idbarang = NEW.barang_idbarang
        ORDER BY ks.idkartu_stok DESC LIMIT 1
    ), 0) INTO v_stok_akhir;

    INSERT INTO kartu_stok
        (jenis_transaksi, masuk, keluar, stock, idtransaksi, idbarang)
    VALUES
        ('M', NEW.jumlah_terima, 0,
         v_stok_akhir + NEW.jumlah_terima,
         NEW.idpenerimaan, NEW.barang_idbarang);
END$$
DELIMITER ;

-- Pemeriksaan:
-- SHOW TRIGGERS LIKE 'detail_penerimaan';
-- SHOW CREATE TRIGGER trg_kartu_stok_masuk;
