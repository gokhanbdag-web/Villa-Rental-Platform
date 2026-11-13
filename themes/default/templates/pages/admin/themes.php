<?php
declare(strict_types=1);

use App\Core\Translator;

$translate = static fn(string $key): string => Translator::get($key);
$activeSlug = strtolower(trim((string)($activeTheme ?? 'default')));
$themeList = is_array($themes ?? null) ? $themes : [];
?>
<style>
.theme-manager-page{--tm-ink:#202b25;--tm-muted:#718078;--tm-line:#e5ebe7;--tm-green:#176b50;max-width:1240px;margin:0 auto;color:var(--tm-ink)}
.theme-manager-page *{box-sizing:border-box}
.theme-manager-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin:0 0 24px}
.theme-manager-heading h1{font-size:clamp(25px,3vw,32px);line-height:1.15;letter-spacing:-.7px;margin:0 0 8px;color:#20352b}
.theme-manager-heading p{margin:0;color:var(--tm-muted);font-size:14px;line-height:1.6;max-width:680px}
.theme-manager-count{display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border:1px solid var(--tm-line);border-radius:999px;background:#fff;color:#56645c;font-size:12px;font-weight:750;white-space:nowrap}
.theme-manager-count:before{content:"";width:8px;height:8px;border-radius:50%;background:#269568}
.theme-manager-alert{padding:13px 16px;margin:0 0 20px;border-radius:11px;border:1px solid #cce7d7;background:#eff8f2;color:#176b50;font-size:13px;font-weight:650}
.theme-manager-alert.error{border-color:#f1d2cf;background:#fff3f1;color:#a33c32}
.theme-manager-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,310px),1fr));gap:18px;align-items:stretch}
.theme-manager-card{position:relative;display:flex;flex-direction:column;min-width:0;overflow:hidden;background:#fff;border:1px solid var(--tm-line);border-radius:16px;box-shadow:0 3px 12px rgba(29,52,39,.035);transition:border-color .18s,box-shadow .18s,transform .18s}
.theme-manager-card:hover{border-color:#cbded2;box-shadow:0 12px 28px rgba(29,52,39,.08);transform:translateY(-2px)}
.theme-manager-card.is-active{border:1.5px solid #8fc5a7;box-shadow:0 0 0 3px rgba(38,149,104,.08),0 8px 22px rgba(29,52,39,.05)}
.theme-manager-card-top{padding:20px 20px 16px;background:linear-gradient(135deg,#f7faf8,#fff);border-bottom:1px solid #edf1ee}
.theme-manager-card.is-active .theme-manager-card-top{background:linear-gradient(135deg,#edf7f0,#fff)}
.theme-manager-card-title{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.theme-manager-card h2{font-size:19px;line-height:1.3;letter-spacing:-.25px;margin:0;color:#26372e}
.theme-manager-badges{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:6px}
.theme-manager-badge{display:inline-flex;align-items:center;min-height:24px;padding:4px 9px;border-radius:999px;background:#f0f3f1;color:#647168;font-size:10px;font-weight:800;letter-spacing:.25px;white-space:nowrap}
.theme-manager-badge.active{background:#dff2e6;color:#176b50}
.theme-manager-card-body{display:flex;flex:1;flex-direction:column;padding:17px 20px 20px}
.theme-manager-description{margin:0 0 19px;min-height:44px;color:#69776f;font-size:13px;line-height:1.65}
.theme-manager-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:0 0 20px}
.theme-manager-meta div{padding:11px 12px;border:1px solid #edf1ee;border-radius:10px;background:#fbfcfb;min-width:0}
.theme-manager-meta dt{margin:0 0 5px;color:#8a968e;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.7px}
.theme-manager-meta dd{margin:0;color:#34443a;font-size:12px;font-weight:700;overflow-wrap:anywhere}
.theme-manager-card-actions{margin-top:auto;padding-top:2px}
.theme-manager-activate{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;min-height:43px;padding:10px 15px;border:0;border-radius:10px;background:#176b50;color:#fff;font-size:13px;font-weight:800;cursor:pointer;transition:background .18s,transform .18s}
.theme-manager-activate:hover{background:#10563f;transform:translateY(-1px)}
.theme-manager-activate:focus-visible{outline:3px solid #a9d9bd;outline-offset:2px}
.theme-manager-current{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;min-height:43px;padding:10px 14px;border:1px solid #cce7d7;border-radius:10px;background:#eff8f2;color:#176b50;font-size:13px;font-weight:800}
.theme-manager-current svg{width:16px;height:16px;flex:none}
.theme-manager-note{display:flex;align-items:flex-start;gap:10px;margin:20px 0 0;padding:14px 16px;border:1px solid #e7ece8;border-radius:12px;background:#fafcfb;color:#718078;font-size:12px;line-height:1.6}
.theme-manager-note strong{color:#405148}
@media(max-width:600px){.theme-manager-heading{display:block;margin-bottom:18px}.theme-manager-count{margin-top:13px}.theme-manager-grid{grid-template-columns:1fr;gap:13px}.theme-manager-card-top{padding:17px}.theme-manager-card-body{padding:15px 17px 17px}.theme-manager-description{min-height:0}.theme-manager-card h2{font-size:17px}}
</style>
<section class="theme-manager-page">
    <div class="theme-manager-heading">
        <div>
            <h1><?= e($translate('admin.themes.title')) ?></h1>
            <p><?= e($translate('admin.themes.intro')) ?></p>
        </div>
        <span class="theme-manager-count"><?= count($themeList) ?> kurulu tema</span>
    </div>
    <?php if (($themeMessage ?? '') !== ''): ?>
        <div role="status" class="theme-manager-alert <?= ($themeMessageType ?? '') === 'error' ? 'error' : '' ?>"><?= e((string)$themeMessage) ?></div>
    <?php endif; ?>
    <div class="theme-manager-grid">
        <?php foreach ($themeList as $theme): ?>
            <?php
                $slug = strtolower(trim((string)($theme['slug'] ?? '')));
                $isActive = $slug !== '' && hash_equals($activeSlug, $slug);
            ?>
            <article class="theme-manager-card<?= $isActive ? ' is-active' : '' ?>">
                <div class="theme-manager-card-top">
                    <div class="theme-manager-card-title">
                        <h2><?= e((string)($theme['name'] ?? $slug)) ?></h2>
                        <div class="theme-manager-badges">
                            <?php if ($slug === 'default'): ?><span class="theme-manager-badge"><?= e($translate('admin.themes.base')) ?></span><?php endif; ?>
                            <?php if ($isActive): ?><span class="theme-manager-badge active">ŞU ANDA AKTİF</span><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="theme-manager-card-body">
                    <p class="theme-manager-description"><?= e((string)($theme['description'] ?? '')) ?></p>
                    <dl class="theme-manager-meta">
                        <div><dt><?= e($translate('admin.themes.version')) ?></dt><dd><?= e((string)($theme['version'] ?? '—')) ?></dd></div>
                        <div><dt><?= e($translate('admin.themes.author')) ?></dt><dd><?= e((string)($theme['author'] ?? '—')) ?></dd></div>
                    </dl>
                    <div class="theme-manager-card-actions">
                        <?php if ($isActive): ?>
                            <div class="theme-manager-current" aria-label="Bu tema zaten aktif">
                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m4 10 4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Bu tema şu anda kullanımda
                            </div>
                        <?php else: ?>
                            <form method="post" action="<?= e(app_url('/admin/themes/activate')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="theme_slug" value="<?= e($slug) ?>">
                                <button class="theme-manager-activate" type="submit">Bu temayı etkinleştir <span aria-hidden="true">→</span></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="theme-manager-note"><span aria-hidden="true">ⓘ</span><span><strong>Tema yedekleme ve geri dönüş:</strong> Aktif temayı değiştirmek site içeriğinizi silmez. Her tema kendi görünümünü kullanır; eksik şablon ve bileşenler varsayılan temadan tamamlanır.</span></div>
</section>
