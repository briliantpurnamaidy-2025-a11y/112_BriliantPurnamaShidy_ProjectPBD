<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/layout.php';

function tampilkanHalamanView(string $kunci, string $sql, ?array $aksi = null): void
{
    requireAccess($kunci);
    $semua = semuaModulView();
    if (!isset($semua[$kunci])) { http_response_code(404); exit('Halaman tidak ditemukan.'); }
    $m = $semua[$kunci];
    $rows = [];
    $error = false;
    try {
        $rows = db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $error = true;
        error_log('[View ' . $m['view'] . '] ' . $e->getMessage());
    }
    mulaiLayout($m['title'], $kunci);
    ?>
    <div class="page-header clean-view-heading">
      <h1><?= viewEsc($m['title']) ?></h1>
      <?php if ($aksi): ?>
        <a class="primary-btn" href="<?= viewEsc($aksi['href']) ?>"><?= viewEsc($aksi['title']) ?></a>
      <?php endif; ?>
    </div>
    <?php if ($flash = flashTake()): ?>
      <div class="notice <?= $flash['type']==='error' ? 'notice-error' : 'notice-info' ?>"><?= viewEsc($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="notice notice-error">Gagal memuat data.</div>
    <?php else: ?>
      <section class="panel full-panel">
        <div class="panel-heading list-heading">
          <h2>Daftar Data</h2>
          <div class="list-actions">
            <label class="sr-only" for="searchRows">Cari data</label>
            <input type="search" id="searchRows" placeholder="Cari..." autocomplete="off">
            <span class="row-indicator"><b id="visibleCount"><?= count($rows) ?></b> data</span>
          </div>
        </div>
        <div class="table-wrap"><table class="data-table" id="masterTable">
          <thead><tr><?php foreach ($m['columns'] as $caption): ?><th><?= viewEsc($caption) ?></th><?php endforeach; ?></tr></thead>
          <tbody>
          <?php foreach ($rows as $r): ?><tr class="filterable-row">
            <?php foreach ($m['columns'] as $column=>$caption): ?><td><?= viewCell($column, $r[$column] ?? null) ?></td><?php endforeach; ?>
          </tr><?php endforeach; ?>
          </tbody>
        </table></div>
        <?php if (!$rows): ?><p class="empty-state">Belum ada data.</p><?php endif; ?>
        <p class="empty-state" id="noMatches" hidden>Tidak ada hasil.</p>
      </section>
    <?php endif; ?>
    <?php
    akhiriLayout();
}
