<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/layout.php';

function tampilkanDetailTransaksi(string $jenis): void
{
    $config = [
        'pengadaan' => [
            'judul' => 'Pengadaan',
            'kunci' => 'idpengadaan',
            'tanggal' => 'dibuat_pada',
            'header' => 'SELECT idpengadaan, dibuat_pada, petugas, nama_vendor, status, subtotal_nilai, ppn, total_nilai FROM v_pengadaan WHERE idpengadaan = ? LIMIT 1',
            'detail' => 'SELECT iddetail_pengadaan, nama_barang, jumlah, harga_satuan, sub_total AS total_baris FROM v_detail_pengadaan WHERE idpengadaan = ? ORDER BY iddetail_pengadaan ASC',
            'detail_id' => 'iddetail_pengadaan',
        ],
        'penerimaan' => [
            'judul' => 'Penerimaan',
            'kunci' => 'idpenerimaan',
            'tanggal' => 'created_at',
            'header' => 'SELECT idpenerimaan, idpengadaan, created_at, petugas, status FROM v_penerimaan WHERE idpenerimaan = ? LIMIT 1',
            'detail' => 'SELECT iddetail_penerimaan, nama_barang, jumlah_terima AS jumlah, harga_satuan_terima AS harga_satuan, sub_total_terima AS total_baris FROM v_detail_penerimaan WHERE idpenerimaan = ? ORDER BY iddetail_penerimaan ASC',
            'detail_id' => 'iddetail_penerimaan',
        ],
        'penjualan' => [
            'judul' => 'Penjualan',
            'kunci' => 'idpenjualan',
            'tanggal' => 'created_at',
            'header' => 'SELECT idpenjualan, created_at, kasir, persen_margin, subtotal_nilai, ppn, total_nilai FROM v_penjualan WHERE idpenjualan = ? LIMIT 1',
            'detail' => 'SELECT iddetail_penjualan, nama_barang, jumlah, harga_satuan, subtotal AS total_baris FROM v_detail_penjualan WHERE idpenjualan = ? ORDER BY iddetail_penjualan ASC',
            'detail_id' => 'iddetail_penjualan',
        ],
    ];

    if (!isset($config[$jenis])) {
        http_response_code(404);
        exit('Halaman tidak ditemukan.');
    }

    requireAccess($jenis);
    $cfg = $config[$jenis];
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!is_int($id) || $id < 1) {
        http_response_code(400);
        exit('ID transaksi tidak valid.');
    }

    $record = null;
    $items = [];
    $error = false;
    try {
        $pdo = db();
        $stmt = $pdo->prepare($cfg['header']);
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $stmt->closeCursor();

        if ($record !== null) {
            $stmt = $pdo->prepare($cfg['detail']);
            $stmt->execute([$id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
        }
    } catch (Throwable $e) {
        $error = true;
        error_log('[Detail ' . $jenis . ' #' . $id . '] ' . $e->getMessage());
    }

    if (!$error && $record === null) {
        http_response_code(404);
    }

    $subtotal = $jenis === 'penerimaan'
        ? array_sum(array_map(static fn (array $item): float => (float)$item['total_baris'], $items))
        : (float)($record['subtotal_nilai'] ?? 0);
    $total = $jenis === 'penerimaan' ? $subtotal : (float)($record['total_nilai'] ?? 0);
    $bolehEditHarga = $jenis === 'penerimaan' && roleCan('penerimaan', 'write');

    mulaiLayout('Detail ' . $cfg['judul'], $jenis);
    ?>
    <div class="dt-page">
        <div class="dt-nav">
            <a class="dt-back" href="<?= viewEsc($jenis) ?>.php#transaksi-<?= $id ?>">
                <span class="dt-back-arrow" aria-hidden="true">←</span>
                Kembali ke <?= viewEsc($cfg['judul']) ?>
            </a>
            <span class="dt-nav-location"><?= viewEsc($cfg['judul']) ?> <span aria-hidden="true">/</span> Detail #<?= $id ?></span>
        </div>

        <?php if ($error): ?>
            <div class="notice notice-error" role="alert">Detail transaksi belum bisa ditampilkan. Periksa koneksi dan SQL View.</div>
        <?php elseif ($record === null): ?>
            <div class="notice notice-error" role="alert">Transaksi #<?= $id ?> tidak ditemukan.</div>
        <?php else: ?>
            <section class="dt-hero" aria-label="Informasi transaksi">
                <div class="dt-hero-main">
                    <div class="dt-hero-heading">
                        <div class="dt-icon" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 4.5h8a2 2 0 0 1 2 2v13H6v-13a2 2 0 0 1 2-2Z" />
                                <path d="M9 4.5V3h6v1.5M9 10h6M9 14h6M9 18h3" />
                            </svg>
                        </div>
                        <div class="dt-title-block">
                            <div class="dt-transaction-code">Transaksi #<?= $id ?></div>
                            <h1>Detail <?= viewEsc($cfg['judul']) ?></h1>
                        </div>
                    </div>
                    <div class="dt-hero-amount">
                        <span><?= $jenis === 'penerimaan' ? 'Nilai Barang Diterima' : 'Total ' . viewEsc($cfg['judul']) ?></span>
                        <strong><?= viewEsc(viewUang($total)) ?></strong>
                    </div>
                </div>

                <div class="dt-meta-grid <?= $jenis === 'penjualan' ? 'dt-meta-grid--three' : '' ?>">
                    <div class="dt-meta-item">
                        <span class="dt-meta-label">Tanggal Transaksi</span>
                        <strong><?= viewEsc(viewTanggal($record[$cfg['tanggal']] ?? null)) ?></strong>
                    </div>
                    <?php if ($jenis === 'pengadaan'): ?>
                        <div class="dt-meta-item"><span class="dt-meta-label">Vendor</span><strong><?= viewEsc($record['nama_vendor'] ?? '') ?></strong></div>
                        <div class="dt-meta-item"><span class="dt-meta-label">Petugas</span><strong><?= viewEsc($record['petugas'] ?? '') ?></strong></div>
                        <div class="dt-meta-item"><span class="dt-meta-label">Status</span><span class="dt-status-wrap"><?= viewCell('status', $record['status'] ?? null) ?></span></div>
                    <?php elseif ($jenis === 'penerimaan'): ?>
                        <div class="dt-meta-item"><span class="dt-meta-label">Referensi Pengadaan</span><strong>
                            <?php if (roleCan('pengadaan')): ?>
                                <a class="dt-ref-link" href="detail_pengadaan.php?id=<?= (int)($record['idpengadaan'] ?? 0) ?>">#<?= (int)($record['idpengadaan'] ?? 0) ?> <span aria-hidden="true">↗</span></a>
                            <?php else: ?>
                                #<?= (int)($record['idpengadaan'] ?? 0) ?>
                            <?php endif; ?>
                        </strong></div>
                        <div class="dt-meta-item"><span class="dt-meta-label">Petugas</span><strong><?= viewEsc($record['petugas'] ?? '') ?></strong></div>
                        <div class="dt-meta-item"><span class="dt-meta-label">Status</span><span class="dt-status-wrap"><?= viewCell('status', $record['status'] ?? null) ?></span></div>
                    <?php else: ?>
                        <div class="dt-meta-item"><span class="dt-meta-label">Kasir</span><strong><?= viewEsc($record['kasir'] ?? '') ?></strong></div>
                        <div class="dt-meta-item"><span class="dt-meta-label">Margin Penjualan</span><strong><?= viewEsc(number_format((float)($record['persen_margin'] ?? 0), 2, ',', '.')) ?>%</strong></div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="dt-items-card" aria-labelledby="dt-items-title">
                <div class="dt-items-heading">
                    <div class="dt-items-heading-main">
                        <h2 id="dt-items-title">Rincian Barang</h2>
                        <span class="dt-items-count"><?= count($items) ?> jenis barang</span>
                    </div>
                    <span class="dt-items-number">#<?= $id ?></span>
                </div>

                <div class="dt-table-scroll">
                    <table class="dt-table">
                        <thead>
                            <tr>
                                <th scope="col">No.</th>
                                <th scope="col">Nama Barang</th>
                                <th scope="col" class="dt-align-right">Jumlah</th>
                                <th scope="col" class="dt-align-right">Harga Satuan</th>
                                <th scope="col" class="dt-align-right">Subtotal</th>
                                <?php if ($bolehEditHarga): ?><th scope="col" class="dt-align-right">Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td class="dt-row-number"><?= $i + 1 ?></td>
                                <td class="dt-item-name"><?= viewEsc($item['nama_barang'] ?? '') ?></td>
                                <td class="dt-align-right"><?= viewEsc(number_format((float)($item['jumlah'] ?? 0), 0, ',', '.')) ?></td>
                                <td class="dt-align-right dt-cell-muted"><?= viewEsc(viewUang($item['harga_satuan'] ?? 0)) ?></td>
                                <td class="dt-align-right dt-line-amount"><?= viewEsc(viewUang($item['total_baris'] ?? 0)) ?></td>
                                <?php if ($bolehEditHarga): ?>
                                    <td class="dt-align-right"><a class="dt-edit-link" href="penerimaan_harga_edit.php?id=<?= (int)$item[$cfg['detail_id']] ?>">Edit harga <span aria-hidden="true">↗</span></a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$items): ?>
                            <tr><td colspan="<?= $bolehEditHarga ? 6 : 5 ?>" class="dt-empty">Belum ada rincian barang untuk transaksi ini.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="dt-items-footer">
                    <div class="dt-footnote"><?= count($items) ?> jenis barang tercatat</div>
                    <div class="dt-totals">
                        <div class="dt-total-line">
                            <span><?= $jenis === 'penerimaan' ? 'Nilai Barang Diterima' : 'Subtotal' ?></span>
                            <strong><?= viewEsc(viewUang($subtotal)) ?></strong>
                        </div>
                        <?php if ($jenis !== 'penerimaan'): ?>
                            <div class="dt-total-line">
                                <span>PPN</span>
                                <strong><?= viewEsc(viewUang($record['ppn'] ?? 0)) ?></strong>
                            </div>
                            <div class="dt-total-final">
                                <span>Total <?= viewEsc($cfg['judul']) ?></span>
                                <strong><?= viewEsc(viewUang($total)) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
    <?php
    akhiriLayout();
}
