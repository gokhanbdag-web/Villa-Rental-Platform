<?php declare(strict_types=1); ?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Güncelleme Merkezi</title><style>
.vu-wrap{max-width:1050px;margin:0 auto;color:#26332b}.vu-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.vu-card{background:#fff;border:1px solid #e7ece8;border-radius:13px;padding:22px;margin:12px 0}.vu-label{color:#77827c;font-size:13px}.vu-value{font-size:26px;font-weight:800;margin:10px 0}.vu-note{color:#53665a;font-size:13px;line-height:1.65}.vu-warn{color:#895d12}@media(max-width:600px){.vu-grid{grid-template-columns:1fr}}
</style></head><body><main class="vu-wrap"><h1>Güncelleme Merkezi</h1><p class="vu-note">Yazılım sürümünü ve üretici tarafından yayımlanan güncellemeleri kontrol edin.</p>
<div class="vu-grid"><section class="vu-card"><div class="vu-label">Kurulu sürüm</div><div class="vu-value"><?= e($installedVersion) ?></div></section>
<section class="vu-card"><div class="vu-label">Güncel sürüm</div><div class="vu-value"><?= !empty($vendorUpdate['connected']) ? e((string)$vendorUpdate['version']) : 'Kontrol edilemedi' ?></div></section></div>
<section class="vu-card"><h2>Güncelleme durumu</h2>
<?php if (empty($vendorUpdate['connected'])): ?><p class="vu-note vu-warn"><?= e((string)($vendorUpdate['error'] ?? 'Güncelleme sunucusuna erişilemiyor.')) ?></p>
<?php elseif (!empty($vendorUpdate['available'])): ?><p class="vu-note">Yeni sürüm yayımlandı. Güvenli ZIP yükleyicisi etkinleştirilene kadar panel üzerinden kurulum yapılamaz.</p>
<?php else: ?><p class="vu-note">Kurulu sürüm güncel.</p><?php endif; ?>
<?php if (!empty($vendorUpdate['notes'])): ?><h2>Sürüm notları</h2><p class="vu-note"><?= nl2br(e((string)$vendorUpdate['notes'])) ?></p><?php endif; ?>
</section></main></body></html>