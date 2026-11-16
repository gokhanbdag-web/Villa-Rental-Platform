<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$routes = (string) file_get_contents($root . '/routes/web.php');
$list = (string) file_get_contents($root . '/themes/default/templates/pages/admin/pages.php');
$form = (string) file_get_contents($root . '/themes/default/templates/pages/admin/page-form.php');

$start = strpos($routes, "/admin/pages'");
$end = strpos($routes, "/page/{slug}", $start === false ? 0 : $start);
$adminRoutes = ($start !== false && $end !== false) ? substr($routes, $start, $end - $start) : '';

$checks = [
    'admin CMS page list derives the selected locale from active languages' =>
        str_contains($adminRoutes, 'SELECT code,name,is_default FROM languages WHERE is_active=1')
        && str_contains($adminRoutes, 'defaultLocale')
        && str_contains($adminRoutes, 'WHERE language_code=?'),
    'admin CMS page list preserves locale in filter, create and edit navigation' =>
        str_contains($list, 'name="lang"')
        && str_contains($list, '/admin/pages/new?lang=')
        && str_contains($list, '?lang=<?= e(rawurlencode($locale)) ?>'),
    'new CMS pages can be created in any active language' =>
        str_contains($adminRoutes, 'language_code')
        && str_contains($adminRoutes, 'SELECT code FROM languages WHERE code=? AND is_active=1 LIMIT 1')
        && str_contains($adminRoutes, 'INSERT INTO cms_pages(language_code,title,slug,content,status,meta_title,meta_description,focus_keyword) VALUES(?,?,?,?,?,?,?,?)'),
    'CMS page edits are scoped to the page language and ID' =>
        str_contains($adminRoutes, 'WHERE id=? AND language_code=?')
        && str_contains($adminRoutes, 'UPDATE cms_pages SET title=?,slug=?,content=?,status=?,meta_title=?,meta_description=?,focus_keyword=? WHERE id=? AND language_code=?'),
    'page editor shows language selection for new pages and locks language on edits' =>
        str_contains($form, 'name="language_code"')
        && str_contains($form, 'foreach($languages as $language)')
        && str_contains($form, 'disabled'),
    'saving a CMS page returns to the same language list' =>
        str_contains($adminRoutes, 'redirect(\'/admin/pages?lang=\' . rawurlencode($locale))'),
];

$failed = [];
foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS' : 'FAIL') . ' ' . $name . PHP_EOL;
    if (!$ok) $failed[] = $name;
}
if ($failed) {
    fwrite(STDERR, 'Failed: ' . implode('; ', $failed) . PHP_EOL);
    exit(1);
}
echo 'All multilingual CMS page checks passed.' . PHP_EOL;
