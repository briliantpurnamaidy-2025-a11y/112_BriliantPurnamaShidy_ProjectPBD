-- OPSIONAL. HANYA jika memang ingin menghapus AKUN dan ROLE gudang dari database.
-- Default versi final: role 2 tidak bisa login, sehingga penghapusan tidak wajib.
-- BACKUP sebelum menjalankan DELETE. Pemeriksaan ini diasumsikan role 2 dan user id=2
-- sesuai screenshot sebelumnya. Jangan ubah ID lain tanpa verifikasi.
SELECT iduser,username,idrole FROM `user` WHERE idrole=2;
SELECT COUNT(*) AS dipakai_pengadaan FROM pengadaan WHERE user_iduser=2;
SELECT COUNT(*) AS dipakai_penerimaan FROM penerimaan WHERE iduser=2;
SELECT COUNT(*) AS dipakai_penjualan FROM penjualan WHERE iduser=2;
SELECT COUNT(*) AS dipakai_retur FROM retur WHERE iduser=2;
SELECT COUNT(*) AS dipakai_margin FROM margin_penjualan WHERE iduser=2;

-- JANGAN jalankan dua perintah DELETE di bawah kecuali semua hitungan=0,
-- tidak ada user lain pada role 2, dan data backup sudah aman.
-- DELETE FROM `user` WHERE iduser=2 AND username='petugas_gudang' AND idrole=2;
-- DELETE FROM `role` WHERE idrole=2 AND nama_role='Petugas Gudang';
