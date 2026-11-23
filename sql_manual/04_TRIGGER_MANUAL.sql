-- JALANKAN MANUAL DI HEIDISQL SEBAGAI SCRIPT UTUH (delimiter $$).
-- BACKUP database sebelum DROP/CREATE. Tidak ada script SQL otomatis dijalankan PHP.
-- idtransaksi menyimpan ID HEADER penerimaan/penjualan/retur.
-- 'M'=masuk dari penerimaan, 'K'=keluar karena penjualan, 'R'=keluar karena retur vendor.
-- Saldo kartu_stok diserialisasi menggunakan FOR UPDATE pada baris barang.

DROP TRIGGER IF EXISTS trg_kartu_stok_masuk;
DROP TRIGGER IF EXISTS trg_kartu_stok_keluar;
DROP TRIGGER IF EXISTS trg_kartu_stok_retur;
DELIMITER $$
CREATE TRIGGER trg_kartu_stok_masuk AFTER INSERT ON detail_penerimaan
FOR EACH ROW
BEGIN
    DECLARE v_id INT;
    DECLARE v_saldo INT DEFAULT 0;
    IF NEW.jumlah_terima <= 0 OR NEW.harga_satuan_terima < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Jumlah/harga penerimaan tidak valid';
    END IF;
    SELECT idbarang INTO v_id FROM barang WHERE idbarang=NEW.barang_idbarang FOR UPDATE;
    SELECT COALESCE((SELECT k.stock FROM kartu_stok k WHERE k.idbarang=NEW.barang_idbarang
           ORDER BY k.idkartu_stok DESC LIMIT 1),0) INTO v_saldo;
    INSERT INTO kartu_stok(jenis_transaksi,masuk,keluar,stock,idtransaksi,idbarang)
    VALUES('M',NEW.jumlah_terima,0,v_saldo+NEW.jumlah_terima,NEW.idpenerimaan,NEW.barang_idbarang);
    -- Harga barang adalah harga beli TERAKHIR yang benar-benar diterima.
    UPDATE barang SET harga=NEW.harga_satuan_terima WHERE idbarang=NEW.barang_idbarang;
END$$

CREATE TRIGGER trg_kartu_stok_keluar AFTER INSERT ON detail_penjualan
FOR EACH ROW
BEGIN
    DECLARE v_id INT;
    DECLARE v_saldo INT DEFAULT 0;
    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Jumlah penjualan harus positif';
    END IF;
    SELECT idbarang INTO v_id FROM barang WHERE idbarang=NEW.idbarang FOR UPDATE;
    SELECT COALESCE((SELECT k.stock FROM kartu_stok k WHERE k.idbarang=NEW.idbarang
           ORDER BY k.idkartu_stok DESC LIMIT 1),0) INTO v_saldo;
    IF v_saldo < NEW.jumlah THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stok tidak mencukupi untuk penjualan';
    END IF;
    INSERT INTO kartu_stok(jenis_transaksi,masuk,keluar,stock,idtransaksi,idbarang)
    VALUES('K',0,NEW.jumlah,v_saldo-NEW.jumlah,NEW.penjualan_idpenjualan,NEW.idbarang);
END$$

-- RETUR adalah bonus. Trigger disiapkan agar retur tidak membuat stok negatif.
CREATE TRIGGER trg_kartu_stok_retur AFTER INSERT ON detail_retur
FOR EACH ROW
BEGIN
    DECLARE v_idbarang INT;
    DECLARE v_idretur BIGINT;
    DECLARE v_kunci INT;
    DECLARE v_saldo INT DEFAULT 0;
    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Jumlah retur harus positif';
    END IF;
    SELECT barang_idbarang INTO v_idbarang FROM detail_penerimaan
    WHERE iddetail_penerimaan=NEW.iddetail_penerimaan;
    SELECT idretur INTO v_idretur FROM retur WHERE idretur=NEW.idretur;
    SELECT idbarang INTO v_kunci FROM barang WHERE idbarang=v_idbarang FOR UPDATE;
    SELECT COALESCE((SELECT k.stock FROM kartu_stok k WHERE k.idbarang=v_idbarang
           ORDER BY k.idkartu_stok DESC LIMIT 1),0) INTO v_saldo;
    IF v_saldo < NEW.jumlah THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stok tidak mencukupi untuk retur';
    END IF;
    INSERT INTO kartu_stok(jenis_transaksi,masuk,keluar,stock,idtransaksi,idbarang)
    VALUES('R',0,NEW.jumlah,v_saldo-NEW.jumlah,v_idretur,v_idbarang);
END$$
DELIMITER ;
