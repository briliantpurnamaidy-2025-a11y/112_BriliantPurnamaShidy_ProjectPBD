-- Semua nama kolom disesuaikan dengan DDL laporan UTS 15 tabel.
-- View transaksi boleh menghasilkan 0 baris selama tabel sumber masih kosong.

CREATE OR REPLACE VIEW v_pengadaan AS
SELECT p.idpengadaan, p.`timestamp` AS dibuat_pada,
       p.user_iduser AS iduser, u.username AS petugas,
       p.vendor_idvendor AS idvendor, v.nama_vendor,
       p.status, p.subtotal_nilai, p.ppn, p.total_nilai
FROM pengadaan p
JOIN `user` u ON p.user_iduser = u.iduser
JOIN vendor v ON p.vendor_idvendor = v.idvendor;

CREATE OR REPLACE VIEW v_detail_pengadaan AS
SELECT dp.iddetail_pengadaan, dp.idpengadaan, dp.idbarang,
       b.nama AS nama_barang, dp.harga_satuan, dp.jumlah, dp.sub_total
FROM detail_pengadaan dp
JOIN barang b ON dp.idbarang = b.idbarang;

CREATE OR REPLACE VIEW v_penerimaan AS
SELECT p.idpenerimaan, p.created_at, p.status,
       p.idpengadaan, p.iduser, u.username AS petugas
FROM penerimaan p
JOIN `user` u ON p.iduser = u.iduser;

CREATE OR REPLACE VIEW v_detail_penerimaan AS
SELECT dp.iddetail_penerimaan, dp.idpenerimaan,
       dp.barang_idbarang AS idbarang, b.nama AS nama_barang,
       dp.jumlah_terima, dp.harga_satuan_terima, dp.sub_total_terima
FROM detail_penerimaan dp
JOIN barang b ON dp.barang_idbarang = b.idbarang;

CREATE OR REPLACE VIEW v_penjualan AS
SELECT p.idpenjualan, p.created_at, p.iduser, u.username AS kasir,
       p.idmargin_penjualan, m.persen AS persen_margin,
       p.subtotal_nilai, p.ppn, p.total_nilai
FROM penjualan p
JOIN `user` u ON p.iduser = u.iduser
JOIN margin_penjualan m ON p.idmargin_penjualan = m.idmargin_penjualan;

CREATE OR REPLACE VIEW v_detail_penjualan AS
SELECT dp.iddetail_penjualan, dp.penjualan_idpenjualan AS idpenjualan,
       dp.idbarang, b.nama AS nama_barang,
       dp.harga_satuan, dp.jumlah, dp.subtotal
FROM detail_penjualan dp
JOIN barang b ON dp.idbarang = b.idbarang;

CREATE OR REPLACE VIEW v_retur AS
SELECT r.idretur, r.created_at, r.idpenerimaan,
       r.iduser, u.username AS petugas
FROM retur r
JOIN `user` u ON r.iduser = u.iduser;

CREATE OR REPLACE VIEW v_detail_retur AS
SELECT dr.iddetail_retur, dr.idretur, dr.iddetail_penerimaan,
       dp.barang_idbarang AS idbarang, b.nama AS nama_barang,
       dr.jumlah, dr.alasan
FROM detail_retur dr
JOIN detail_penerimaan dp ON dr.iddetail_penerimaan = dp.iddetail_penerimaan
JOIN barang b ON dp.barang_idbarang = b.idbarang;

CREATE OR REPLACE VIEW v_kartu_stok AS
SELECT ks.idkartu_stok, ks.idbarang, b.nama AS nama_barang,
       ks.jenis_transaksi, ks.masuk, ks.keluar, ks.stock,
       ks.idtransaksi, ks.created_at
FROM kartu_stok ks
JOIN barang b ON ks.idbarang = b.idbarang;
