<?php
declare(strict_types=1);

use App\Core\Translator;

$translate = static fn(string $key): string => Translator::get($key);
?>
<section class="theme-manager-page">
    <h1><?= e($translate('admin.themes.title')) ?></h1>
    <p><?= e($translate('admin.themes.intro')) ?></p>
    <?php if (($themeMessage ?? '') !== ''): ?>
        <div role="status" class="<?= e((string)($themeMessageType ?? 'success')) ?>"><?= e((string)$themeMessage) ?></div>
    <?php endif; ?>
    <div class="theme-manager-grid">
        <?php foreach (($themes ?? []) as $theme): ?>
            <article class="theme-manager-card">
                <header>
                    <h2><?= e((string)$theme['name']) ?></h2>
                    <?php if (($theme['slug'] ?? '') === 'default'): ?>
                        <span><?= e($translate('admin.themes.base')) ?></span>
                    <?php endif; ?>
                    <?php if (($theme['slug'] ?? '') === ($activeTheme ?? 'default')): ?>
                        <strong><?= e($translate('admin.themes.active')) ?></strong>
                    <?php endif; ?>
                </header>
                <p><?= e((string)($theme['description'] ?? '')) ?></p>
                <dl>
                    <dt><?= e($translate('admin.themes.version')) ?></dt>
                    <dd><?= e((string)($theme['version'] ?? '')) ?></dd>
                    <dt><?= e($translate('admin.themes.author')) ?></dt>
                    <dd><?= e((string)($theme['author'] ?? '')) ?></dd>
                </dl>
                <?php if (($theme['slug'] ?? '') !== ($activeTheme ?? 'default')): ?>
                    <form method="post" action="<?= e(app_url('/admin/themes/activate')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="theme_slug" value="<?= e((string)$theme['slug']) ?>">
                        <button type="submit"><?= e($translate('admin.themes.activate')) ?></button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <p><?= e($translate('admin.themes.fallback_note')) ?></p>
</section>
