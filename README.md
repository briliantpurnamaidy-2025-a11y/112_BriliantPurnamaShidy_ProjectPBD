# Toko Sembako Gen Z — Paket Final Demo (versi revisi)

Paket **REPLACE** untuk proyek `toko_sembako_gen_z_integrasi_otomatis` dari ZIP lama. Ekstrak di `C:\laragon\www\`, pilih **Replace files in destination**. **Backup folder dan database terlebih dahulu**. `config.local.php` tidak disertakan, jadi setelan lokal tidak ditimpa. **Baca PETUNJUK_REPLACE.txt**. Perubahan UI lokal mungkin tertimpa untuk file dengan nama sama.

## Isi utama
- Login/Logout dan otorisasi berdasarkan tabel `user` dan `role` yang sudah ada. Role 1 Administrator, 3 Kasir, 4 Manajer. Role 2 Petugas Gudang **tidak diberi izin login**, tanpa menghapus histori/relasi database.
- Pengadaan mandiri, satu transaksi bisa beberapa barang, PPN 12%, status awal A=Proses.
- Penerimaan berdasarkan pengadaan, bisa bertahap; sistem menampilkan sisa pesanan, harga aktual bisa diedit saat input. Setelah semua rincian terpenuhi, status pengadaan dan semua penerimaan di pengadaan tersebut menjadi S=Selesai.
- Harga beli barang otomatis menggunakan harga penerimaan terakhir (Trigger). Koreksi harga setelah transaksi tersedia lewat menu Detail Penerimaan; jumlah penerimaan yang sudah diposting **tidak boleh diedit langsung** untuk menjaga stok.
- Penjualan kasir, margin aktif tunggal, perhitungan harga melalui MySQL Function, PPN 11%, validasi stok, pencatatan keluar melalui Trigger.
- Retur ke vendor sebagai bonus, juga mencatat stok keluar melalui Trigger khusus.
- Kartu stok hanya baca (tidak ada tombol CRUD), filter barang memanggil Stored Procedure. Filter satuan di Barang juga memanggil Stored Procedure.
- Satuan, Vendor, Barang, Akun User: pengelolaan sesuai hak akses. Data status aktif/nonaktif terlihat dan bisa difilter. Barang yang sudah dipakai transaksi lebih aman dinonaktifkan dibanding dihapus.
- 15 file halaman untuk 15 tabel PDM dipertahankan; 16 SQL Views (15 lama ditambah `v_margin_semua` agar margin nonaktif juga tampil).
- **TIDAK ADA** halaman tombol uji Trigger/SP/Function.

## INSTALASI — HARUS DIKERJAKAN MANUAL

1. **Backup** folder proyek lama dan **export database `praktikumpbd`** lengkap dari HeidiSQL terlebih dahulu.
2. Ekstrak folder `toko_sembako_gen_z_integrasi_otomatis` ke `C:\laragon\www\` dan setujui overwrite file yang sama setelah backup.
3. `config.local.php` lama tidak ada di ZIP sehingga tetap digunakan. Jika belum ada, salin `config.example.php` menjadi `config.local.php` lalu isi konfigurasi lokal.
4. Buka **HeidiSQL**, pastikan database yang aktif benar (contoh `praktikumpbd`). File-file `sql_manual/` **TIDAK PERNAH DIEKSEKUSI OTOMATIS** oleh aplikasi. Jalankan manual dan berurutan:
   - `01_SEMUA_VIEW_MANUAL.sql` (15 View lama + 1 View margin)
   - `02_FUNCTION_MANUAL.sql` (harga jual + total dengan PPN)
   - `03_STORED_PROCEDURE_MANUAL.sql` (filter satuan + riwayat stok)
   - `04_TRIGGER_MANUAL.sql` (**penting:** gunakan Execute SQL file untuk `DELIMITER $$` dan pastikan semua tiga Trigger berhasil dibuat)
   - `05_VERIFIKASI_BACA_SAJA.sql` untuk membaca hasil.
   - `00_STRUKTUR_OPSIONAL_MANUAL.sql`: opsional, untuk memperbesar ID referensi dan persiapan jenis barang UAS. **Jangan dijalankan sebelum dibaca.**
5. Akun lama kemungkinan menyimpan password sebagai teks biasa (berdasarkan contoh DML UTS). Untuk keamanan, login hanya menerima password yang di-hash oleh `password_hash`:
   - Buka **Terminal Laragon**, arahkan ke folder proyek lama yang sudah direplace.
   - Jalankan `php tools/generate_password_sql.php` **tiga kali** untuk `admin_pro`, `kasir`, dan `manajer` dengan sandi baru masing-masing.
   - Salin **hasil SQL UPDATE** yang dicetak ke HeidiSQL dan **eksekusi MANUAL**. Alat ini tidak mengubah MySQL sendiri.
   - Jangan menjalankan generator untuk `petugas_gudang`; akunnya tetap tercatat tetapi akses aplikasi ditolak.
6. Pastikan Nginx berjalan di port 8080 dan MySQL Laragon aktif. Buka:
   `http://localhost:8080/toko_sembako_gen_z_integrasi_otomatis/login.php`
7. Login sebagai Administrator, lalu coba alur bisnis di bawah.

## Hak akses
| Menu | Administrator | Manajer | Kasir |
|---|---|---|---|
| Dashboard | Ya | Ya | Ya |
| Master Barang / Satuan | Kelola | Baca | Barang baca, Satuan baca |
| Vendor | Kelola | Baca | Tidak |
| User/Role | User kelola, Role baca | Baca | Tidak |
| Margin | Kelola | Kelola | Tidak |
| Pengadaan | Kelola | Kelola | Tidak |
| Penerimaan | Kelola | Baca | Tidak |
| Penjualan | Kelola | Baca | Kelola |
| Kartu Stok | Baca | Baca | Baca |
| Retur vendor | Kelola | Baca | Tidak |

Hak akses diperiksa **di server**, bukan hanya sidebar. ID petugas transaksi diambil otomatis dari session login, tidak dari form.

## Pengujian demo (stok awal barang uji sebaiknya 0)
1. Login Administrator. Buat satu barang uji dan satu vendor (atau pakai yang ada).
2. Buka Pengadaan → Buat pengadaan 100 unit, harga 10.000; lihat detail dan subtotal 1.000.000, PPN 120.000, total 1.120.000. **Stok tetap nol.**
3. Penerimaan → pilih pengadaan → terima 40 unit dengan harga aktual 10.500. Stok menjadi 40 dan harga dasar barang berubah menjadi 10.500, status pengadaan masih Proses dengan sisa 60.
4. Penerimaan kedua → terima 60 unit sisa. Stok menjadi 100, status pengadaan & penerimaan menjadi Selesai.
5. Ubah margin aktif, mis. menjadi 30%, lalu perhatikan Harga Jual Barang berubah untuk penjualan berikutnya (transaksi lampau tidak berubah).
6. Logout, masuk sebagai Kasir. Buat penjualan 10 unit, lihat subtotal+PPN 11%, kartu stok berkurang menjadi 90.
7. Coba jual 100 unit; harus ditolak dan stok tetap 90.
8. Bonus Retur: Administrator dapat memilih detail penerimaan lalu retur sebagian; stok berkurang. Jumlah retur tidak boleh melebihi jumlah diterima dan stok tersedia.

**Jangan pakai barang dengan histori stok lama untuk demo angka dari nol**, atau sesuaikan ekspektasi dengan saldo awal yang sebenarnya.

## Batasan / hal yang tetap perlu diuji di komputer kamu
- Paket dibuat dari **PDM dan kode arsip**, bukan dump SQL aktual terbaru dari HeidiSQL. Struktur tabel di database saat ini belum dapat dibandingkan secara langsung.
- PHP syntax (`php -l`) bisa diperiksa, tetapi MySQL Laragon kamu tidak dapat diakses dari lingkungan pembuatan paket; semua tes end-to-end harus dilakukan secara lokal.
- Tabel harga, subtotal, dan total pada PDM bertipe `INT`: kode membatasi transaksi besar agar tidak overflow. Harga jual menggunakan *markup terhadap harga beli* sesuai rancangan sebelumnya; jika dosen mendefinisikan gross profit margin lain, rumus harus disamakan.
- Kode `jenis` barang pada PDM adalah `CHAR(1)`. Untuk 10–20 kategori UAS perlu perubahan manual ke `VARCHAR(10)` (lihat `00_STRUKTUR_OPSIONAL_MANUAL.sql`) sebelum mengisi kode kategori multi-karakter. Form `barang.php` sudah menerima kode 1–2 karakter, tetapi database perlu diubah manual terlebih dahulu.
- Semua transaksi stok yang sudah diposting diperlakukan final; penghapusan detail transaksi secara manual di HeidiSQL **tidak** mengembalikan saldo. Gunakan data uji baru dan jangan hapus riwayat dengan tangan.
- Script `sql/` lama disimpan sebagai **referensi historis**. Untuk versi final, gunakan hanya folder `sql_manual/`.

## Persiapan UAS (opsional)
File `sql_manual/06_SEED_100_BARANG_UAS_OPSIONAL.sql` berisi 100 contoh barang dengan 15 kode kategori. **Sebelum dijalankan MANUAL**, ekspor database, jalankan perubahan `barang.jenis` ke `VARCHAR(10)` dan cek master satuan. Jangan gunakan script ini untuk menambah data sebelum demo jika belum dibutuhkan.

## Jika ingin benar-benar menghapus role Petugas Gudang
Untuk keamanan, role 2 **sudah ditolak oleh aplikasi**, tetapi akun/role tidak dihapus otomatis agar foreign key dan histori tidak hilang. File `sql_manual/07_HAPUS_PETUGAS_GUDANG_OPSIONAL.sql` berisi query pemeriksaan dan DELETE yang masih dalam komentar; jalankan manual **hanya jika** semua tabel terkait tidak memiliki referensi dan backup sudah dibuat. Penghapusan bukan syarat aplikasi bisa demo.

## Catatan pembaruan paket replace
`barang.php` menerima kode jenis barang 1–2 karakter. Untuk dua karakter, **wajib** ubah `barang.jenis` menjadi `VARCHAR(10)` secara manual di HeidiSQL terlebih dahulu, karena PDM awal `CHAR(1)`. SQL lama di `sql/` mungkin tetap ada di folder karena overwrite tidak menghapus file yang tidak ada di ZIP. Gunakan hanya `sql_manual/`.
