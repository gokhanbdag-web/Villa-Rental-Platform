<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$landing = file_get_contents($root . '/themes/default/templates/pages/landing.php');
if (!is_string($landing)) {
    throw new RuntimeException('Homepage landing template could not be read.');
}

if (str_contains($landing, 'class="mobile-hero-search"')) {
    throw new RuntimeException('Redundant standalone mobile hero search form must not be rendered.');
}

$marker = '/* Mobile-only hero search emphasis. Desktop rules above remain untouched. */';
$start = strpos($landing, $marker);
$end = $start === false ? false : strpos($landing, '</style></head>', $start);
if ($start === false || $end === false) {
    throw new RuntimeException('Final mobile-only hero search rules were not found.');
}
$mobileRules = substr($landing, $start, $end - $start);
$required = [
    '@media(max-width:600px)',
    'body .hero-filter-panel{',
    'order:-1!important',
    'content:"VİLLANI BUL"',
    'grid-template-columns:repeat(2,minmax(0,1fr))!important',
    'body .hero-filter-panel .destination-field,',
    'body .hero-filter-panel .guest-field{',
    'min-height:64px!important',
    'background:var(--villa-primary)!important',
    'body .hero-copy{',
    'body .hero-card{padding:17px 13px 20px!important;gap:12px!important}',
    'body .hero-copy h1{margin:4px auto 6px!important;font-size:clamp(24px,6.4vw,30px)!important;line-height:1.08!important}',
    'body .hero-copy p{font-size:12px!important;line-height:1.35!important}',
    'body .hero-copy .eyebrow{font-size:10px!important;letter-spacing:1.3px!important}',
];
foreach ($required as $rule) {
    if (!str_contains($mobileRules, $rule)) {
        throw new RuntimeException('Mobile hero search prominence rule is missing: ' . $rule);
    }
}

$desktopStart = strpos($landing, '@media(min-width:901px)');
$desktopEnd = $desktopStart === false ? false : strpos($landing, '</style></head>', $desktopStart);
if ($desktopStart === false || $desktopEnd === false) {
    throw new RuntimeException('Existing desktop hero layout rules could not be verified.');
}
$desktopRules = substr($landing, $desktopStart, $desktopEnd - $desktopStart);
if (!str_contains($desktopRules, 'grid-template-columns:minmax(0,.82fr) minmax(0,1.28fr)!important;')
    || !str_contains($desktopRules, 'body .hero-card .hero-filter-panel form#homeSearch.search-enhanced > .search-submit')) {
    throw new RuntimeException('Existing desktop hero layout rules were unexpectedly changed.');
}

echo "Mobile hero search prominence and desktop isolation smoke test passed.\n";
