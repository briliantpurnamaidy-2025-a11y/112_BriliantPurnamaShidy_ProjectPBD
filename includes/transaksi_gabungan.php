<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/layout.php';

function tampilkanTransaksiGabungan(string $jenis): void
{
    $opsi = [
        'pengadaan' => [
            'judul' => 'Pengadaan',
            'kunci' => 'idpengadaan',
            'tanggal' => 'dibuat_pada',
            'header' => 'SELECT idpengadaan, dibuat_pada, petugas, nama_vendor, status, subtotal_nilai, ppn, total_nilai FROM v_pengadaan ORDER BY idpengadaan DESC LIMIT 100',
            'detail' => 'SELECT idpengadaan, nama_barang, jumlah, harga_satuan, sub_total AS total_baris FROM v_detail_pengadaan WHERE idpengadaan IN (%s) ORDER BY iddetail_pengadaan ASC',
            'detail_qty' => 'jumlah',
            'detail_harga' => 'harga_satuan',
            'tambah' => 'pengadaan_tambah.php',
            'tombol' => 'Tambah Pengadaan',
        ],
        'penerimaan' => [
            'judul' => 'Penerimaan',
            'kunci' => 'idpenerimaan',
            'tanggal' => 'created_at',
            'header' => 'SELECT idpenerimaan, idpengadaan, petugas, status, created_at FROM v_penerimaan ORDER BY idpenerimaan DESC LIMIT 100',
            'detail' => 'SELECT iddetail_penerimaan, idpenerimaan, nama_barang, jumlah_terima, harga_satuan_terima, sub_total_terima AS total_baris FROM v_detail_penerimaan WHERE idpenerimaan IN (%s) ORDER BY iddetail_penerimaan ASC',
            'detail_qty' => 'jumlah_terima',
            'detail_harga' => 'harga_satuan_terima',
            'tambah' => 'penerimaan_tambah.php',
            'tombol' => 'Catat Penerimaan',
        ],
        'penjualan' => [
            'judul' => 'Penjualan',
            'kunci' => 'idpenjualan',
            'tanggal' => 'created_at',
            'header' => 'SELECT idpenjualan, created_at, kasir, persen_margin, subtotal_nilai, ppn, total_nilai FROM v_penjualan ORDER BY idpenjualan DESC LIMIT 100',
            'detail' => 'SELECT idpenjualan, nama_barang, jumlah, harga_satuan, subtotal AS total_baris FROM v_detail_penjualan WHERE idpenjualan IN (%s) ORDER BY iddetail_penjualan ASC',
            'detail_qty' => 'jumlah',
            'detail_harga' => 'harga_satuan',
            'tambah' => 'penjualan_tambah.php',
            'tombol' => 'Tambah Penjualan',
        ],
    ];
    if (!isset($opsi[$jenis])) {
        http_response_code(404);
        exit('Halaman tidak ditemukan.');
    }
    requireAccess($jenis);
    $cfg = $opsi[$jenis];
    $rows = [];
    $detailMap = [];
    $error = false;
    try {
        $pdo = db();
        $rows = $pdo->query($cfg['header'])->fetchAll(PDO::FETCH_ASSOC);
        $ids = array_values(array_unique(array_map(static fn(array $row): int => (int)$row[$cfg['kunci']], $rows)));
        if ($ids) {
            $holders = implode(',', array_fill(0, count($ids), '?'));
            $q = $pdo->prepare(sprintf($cfg['detail'], $holders));
            foreach ($ids as $i => $id) {
                $q->bindValue($i + 1, $id, PDO::PARAM_INT);
            }
            $q->execute();
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $line) {
                $detailMap[(int)$line[$cfg['kunci']]][] = $line;
            }
        }
    } catch (Throwable $e) {
        $error = true;
        error_log('[Transaksi Gabungan ' . $jenis . '] ' . $e->getMessage());
    }
    $newId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    mulaiLayout($cfg['judul'], $jenis);
    ?>
    <div class="page-header clean-view-heading">
        <h1><?= viewEsc($cfg['judul']) ?></h1>
        <?php if (roleCan($jenis, 'write')): ?>
            <a class="primary-btn" href="<?= viewEsc($cfg['tambah']) ?>"><?= viewEsc($cfg['tombol']) ?></a>
        <?php endif; ?>
    </div>
    <?php if ($flash = flashTake()): ?>
        <div class="notice <?= ($flash['type'] ?? '') === 'error' ? 'notice-error' : 'notice-info' ?>"><?= viewEsc($flash['message'] ?? '') ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="notice notice-error">Data transaksi belum bisa ditampilkan. Periksa koneksi dan SQL View.</div>
    <?php else: ?>
    <section class="panel full-panel transaction-list">
        <div class="panel-heading list-heading">
            <h2>Daftar <?= viewEsc($cfg['judul']) ?></h2>
            <div class="list-actions">
                <label class="sr-only" for="searchRows">Cari transaksi</label>
                <input id="searchRows" type="search" placeholder="Cari transaksi" autocomplete="off">
                <span class="row-indicator"><b id="visibleCount"><?= count($rows) ?></b> data</span>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table transaction-table" id="masterTable">
                <thead>
                <tr>
                    <th>ID</th><th>Tanggal</th><th>Informasi</th><th>Barang</th><th><?= $jenis === 'penjualan' ? 'Margin' : 'Status' ?></th><th>Total</th><th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $id = (int)$r[$cfg['kunci']];
                    $lines = $detailMap[$id] ?? [];
                    $total = $jenis === 'penerimaan'
                        ? array_sum(array_map(static fn(array $item): float => (float)$item['total_baris'], $lines))
                        : (float)($r['total_nilai'] ?? 0);
                ?>
                    <tr class="filterable-row <?= $id === $newId ? 'transaction-recent' : '' ?>" id="transaksi-<?= $id ?>">
                        <td class="transaction-id">#<?= $id ?></td>
                        <td class="transaction-date"><?= viewEsc(viewTanggal($r[$cfg['tanggal']] ?? null)) ?></td>
                        <td class="transaction-info">
                            <?php if ($jenis === 'pengadaan'): ?>
                                <strong><?= viewEsc($r['nama_vendor'] ?? '') ?></strong><span><?= viewEsc($r['petugas'] ?? '') ?></span>
                            <?php elseif ($jenis === 'penerimaan'): ?>
                                <strong>Pengadaan #<?= (int)($r['idpengadaan'] ?? 0) ?></strong><span><?= viewEsc($r['petugas'] ?? '') ?></span>
                            <?php else: ?>
                                <strong><?= viewEsc($r['kasir'] ?? '') ?></strong>
                            <?php endif; ?>
                        </td>
                        <td class="transaction-items">
                            <?php if (!$lines): ?><span>Belum ada barang.</span><?php endif; ?>
                            <?php foreach ($lines as $line): ?>
                                <div class="transaction-item">
                                    <span class="transaction-item-name"><?= viewEsc($line['nama_barang'] ?? '') ?></span>
                                    <span class="transaction-item-calc"><?= (int)$line[$cfg['detail_qty']] ?> × <?= viewEsc(viewUang($line[$cfg['detail_harga']])) ?></span>
                                    <strong><?= viewEsc(viewUang($line['total_baris'])) ?></strong>
                                    <?php if ($jenis === 'penerimaan' && roleCan('penerimaan', 'write')): ?>
                                        <a class="transaction-edit" href="penerimaan_harga_edit.php?id=<?= (int)$line['iddetail_penerimaan'] ?>">Edit harga</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td class="transaction-status">
                            <?php if ($jenis === 'penjualan'): ?>
                                <?= viewEsc(number_format((float)($r['persen_margin'] ?? 0), 2, ',', '.') . '%') ?>
                            <?php else: ?>
                                <?= viewCell('status', $r['status'] ?? null) ?>
                            <?php endif; ?>
                        </td>
                        <td class="transaction-total">
                            <strong><?= viewEsc(viewUang($total)) ?></strong>
                            <?php if ($jenis !== 'penerimaan'): ?>
                                <span>PPN <?= viewEsc(viewUang($r['ppn'] ?? 0)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="transaction-action">
                            <a class="transaction-detail-button" href="detail_<?= viewEsc($jenis) ?>.php?id=<?= $id ?>" aria-label="Lihat detail <?= viewEsc($jenis) ?> nomor <?= $id ?>">Lihat Detail <span aria-hidden="true">→</span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!$rows): ?><p class="empty-state">Belum ada transaksi.</p><?php endif; ?>
        <p class="empty-state" id="noMatches" hidden>Data tidak ditemukan.</p>
    </section>
    <?php endif; ?>
    <?php
    akhiriLayout();
}
