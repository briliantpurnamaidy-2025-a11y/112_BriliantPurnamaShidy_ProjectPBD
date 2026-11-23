
CREATE OR REPLACE VIEW v_master_role AS
SELECT idrole, nama_role FROM `role`;

CREATE OR REPLACE VIEW v_master_user AS
SELECT u.iduser, u.username, u.idrole, r.nama_role FROM `user` u JOIN `role` r ON r.idrole=u.idrole;

CREATE OR REPLACE VIEW v_master_satuan AS
SELECT idsatuan, nama_satuan, status FROM satuan;

CREATE OR REPLACE VIEW v_master_barang AS
SELECT b.idbarang, b.jenis, b.nama AS nama_barang, b.idsatuan, s.nama_satuan, b.harga, b.status
FROM barang b JOIN satuan s ON s.idsatuan=b.idsatuan;

CREATE OR REPLACE VIEW v_master_vendor AS
SELECT idvendor, nama_vendor, badan_hukum, status FROM vendor;

CREATE OR REPLACE VIEW v_margin_aktif AS
SELECT idmargin_penjualan, created_at, persen, status, iduser, updated_at
FROM margin_penjualan WHERE status=1;

CREATE OR REPLACE VIEW v_margin_semua AS
SELECT m.idmargin_penjualan, m.created_at, m.persen, m.status, m.iduser,
       u.username AS petugas, m.updated_at
FROM margin_penjualan m JOIN `user` u ON u.iduser=m.iduser;

CREATE OR REPLACE VIEW v_pengadaan AS
SELECT p.idpengadaan, p.`timestamp` AS dibuat_pada, p.user_iduser AS iduser,
       u.username AS petugas, p.vendor_idvendor AS idvendor, v.nama_vendor,
       p.status, p.subtotal_nilai, p.ppn, p.total_nilai
FROM pengadaan p JOIN `user` u ON u.iduser=p.user_iduser
JOIN vendor v ON v.idvendor=p.vendor_idvendor;

CREATE OR REPLACE VIEW v_detail_pengadaan AS
SELECT d.iddetail_pengadaan, d.idpengadaan, d.idbarang, b.nama AS nama_barang,
       d.harga_satuan, d.jumlah, d.sub_total
FROM detail_pengadaan d JOIN barang b ON b.idbarang=d.idbarang;

CREATE OR REPLACE VIEW v_penerimaan AS
SELECT p.idpenerimaan, p.created_at, p.status, p.idpengadaan,
       p.iduser, u.username AS petugas
FROM penerimaan p JOIN `user` u ON u.iduser=p.iduser;

CREATE OR REPLACE VIEW v_detail_penerimaan AS
SELECT d.iddetail_penerimaan, d.idpenerimaan, d.barang_idbarang AS idbarang,
       b.nama AS nama_barang, d.jumlah_terima, d.harga_satuan_terima, d.sub_total_terima
FROM detail_penerimaan d JOIN barang b ON b.idbarang=d.barang_idbarang;

CREATE OR REPLACE VIEW v_penjualan AS
SELECT p.idpenjualan, p.created_at, p.iduser, u.username AS kasir,
       p.idmargin_penjualan, m.persen AS persen_margin,
       p.subtotal_nilai, p.ppn, p.total_nilai
FROM penjualan p JOIN `user` u ON u.iduser=p.iduser
JOIN margin_penjualan m ON m.idmargin_penjualan=p.idmargin_penjualan;

CREATE OR REPLACE VIEW v_detail_penjualan AS
SELECT d.iddetail_penjualan, d.penjualan_idpenjualan AS idpenjualan,
       d.idbarang, b.nama AS nama_barang, d.harga_satuan, d.jumlah, d.subtotal
FROM detail_penjualan d JOIN barang b ON b.idbarang=d.idbarang;

CREATE OR REPLACE VIEW v_retur AS
SELECT r.idretur, r.created_at, r.idpenerimaan, r.iduser, u.username AS petugas
FROM retur r JOIN `user` u ON u.iduser=r.iduser;

CREATE OR REPLACE VIEW v_detail_retur AS
SELECT d.iddetail_retur, d.idretur, d.iddetail_penerimaan,
       p.barang_idbarang AS idbarang, b.nama AS nama_barang, d.jumlah, d.alasan
FROM detail_retur d JOIN detail_penerimaan p ON p.iddetail_penerimaan=d.iddetail_penerimaan
JOIN barang b ON b.idbarang=p.barang_idbarang;

CREATE OR REPLACE VIEW v_kartu_stok AS
SELECT k.idkartu_stok, k.idbarang, b.nama AS nama_barang, k.jenis_transaksi,
       k.masuk, k.keluar, k.stock, k.idtransaksi, k.created_at
FROM kartu_stok k JOIN barang b ON b.idbarang=k.idbarang;
