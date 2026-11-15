<?php
declare(strict_types=1);

// Regression coverage for the responsive footer layout.

$root = dirname(__DIR__);
$css = file_get_contents($root . '/themes/default/style.css');
$footer = file_get_contents($root . '/themes/default/templates/partials/footer.php');

if (!is_string($css) || !is_string($footer)) {
    throw new RuntimeException('Footer layout source files could not be read.');
}

$mobileStart = strpos($css, '@media(max-width:600px){', strpos($css, '/* Footer widget layout: four equal columns above, 7/12 + 5/12 below. */'));
if ($mobileStart === false) {
    throw new RuntimeException('Mobile footer responsive rules were not found.');
}
$mobileEnd = strpos($css, "\n\n/* Remove unwanted decorative borders", $mobileStart);
if ($mobileEnd === false) {
    throw new RuntimeException('Mobile footer responsive rules were not closed.');
}
$mobileRules = substr($css, $mobileStart, $mobileEnd - $mobileStart);

$requiredRules = [
    '.footer-row-primary,',
    '.footer-row-secondary{',
    'grid-template-columns:minmax(0,1fr)',
    '.footer-widget{',
    'width:100%',
    'text-align:center',
    '.footer-about-logo{',
    'margin-left:auto!important',
    '.footer-copyright-content{',
];
foreach ($requiredRules as $rule) {
    if (!str_contains($mobileRules, $rule)) {
        throw new RuntimeException('Mobile footer layout is missing required rule: ' . $rule);
    }
}

if (!str_contains($footer, 'footer-widget-1') || !str_contains($footer, 'footer-widget-6')) {
    throw new RuntimeException('Footer must retain all six configurable widget columns.');
}
if (!str_contains($css, '.footer-row-primary{grid-template-columns:repeat(4,minmax(0,1fr))}')) {
    throw new RuntimeException('Desktop footer four-column layout must remain intact.');
}
if (!str_contains($css, '.footer-row-secondary{grid-template-columns:minmax(0,7fr) minmax(0,5fr)}')) {
    throw new RuntimeException('Desktop footer secondary row layout must remain intact.');
}

echo "Mobile footer single-column, full-width and centered layout smoke test passed.\n";
