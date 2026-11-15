<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$landing = file_get_contents($root . '/themes/default/templates/pages/landing.php');
if (!is_string($landing)) {
    throw new RuntimeException('Homepage template could not be read.');
}

if (substr_count($landing, 'data-location-autocomplete>') !== 2) {
    throw new RuntimeException('Desktop and mobile destination fields must both enable location autocomplete.');
}
foreach ([
    'id="homeDestination"',
    'id="mobileDestination"',
    'id="homeDestinationOptions"',
    'id="mobileDestinationOptions"',
    'name="location_id"',
    "document.querySelectorAll('[data-location-autocomplete]')",
    "app_url('/locations/search')",
    "input[name=\"location_id\"]",
    'Lütfen açılan önerilerden bir lokasyon seçin.',
    '.mobile-premium-search__destination{grid-column:1/-1;min-height:68px;position:relative;z-index:8}',
] as $required) {
    if (!str_contains($landing, $required)) {
        throw new RuntimeException('Destination autocomplete integration is incomplete: ' . $required);
    }
}

$mobileStart = strpos($landing, '<form class="mobile-premium-search__form"');
$mobileEnd = $mobileStart === false ? false : strpos($landing, '</form>', $mobileStart);
$mobileForm = ($mobileStart !== false && $mobileEnd !== false)
    ? substr($landing, $mobileStart, $mobileEnd - $mobileStart)
    : '';
if ($mobileForm === '' || !str_contains($mobileForm, 'id="mobileDestination"')
    || !str_contains($mobileForm, 'id="mobileDestinationOptions"')
    || !str_contains($mobileForm, 'name="location_id"')) {
    throw new RuntimeException('Mobile destination input must submit the selected canonical location ID.');
}

echo "Desktop and mobile destination autocomplete smoke test passed.\n";
