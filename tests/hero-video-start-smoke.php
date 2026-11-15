<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$landing = file_get_contents($root . '/themes/default/templates/pages/landing.php');
$themeManager = file_get_contents($root . '/app/Core/ThemeManager.php');
$routes = file_get_contents($root . '/routes/web.php');
$admin = file_get_contents($root . '/themes/default/templates/pages/admin/theme-design.php');

foreach (['landing'=>$landing,'ThemeManager'=>$themeManager,'routes'=>$routes,'admin'=>$admin] as $name=>$content) {
    if (!is_string($content)) {
        throw new RuntimeException($name . ' source could not be read.');
    }
}

$checks = [
    'landing' => [
        'const requestedStart=<?= $heroVideoStartSeconds ?>',
        "const readinessEvents=['loadedmetadata','durationchange','loadeddata','canplay']",
        'video.currentTime=target',
        "video.addEventListener('seeked',resume,{once:true})",
        '&amp;start=<?= $heroVideoStartSeconds ?>',
    ],
    'ThemeManager' => [
        'public static function saveHeroVideoSettings',
        "'hero_video_start_seconds'=>(string)max(0,min(86400,$startSeconds))",
        '$expectedStart = max(0, min(86400, $startSeconds));',
        "($stored['hero_video_start_seconds'] ?? -1)",
    ],
    'routes' => [
        "'/admin/theme-design/hero-video-settings'",
        "(int)($_POST['hero_video_start_seconds'] ?? 0)",
    ],
    'admin' => [
        'name="hero_video_start_seconds"',
        'id="heroVideoStartSeconds"',
    ],
];

foreach ($checks as $source=>$needles) {
    $content = $$source;
    foreach ($needles as $needle) {
        if (!str_contains($content, $needle)) {
            throw new RuntimeException($source . ' hero video start integration is missing: ' . $needle);
        }
    }
}

echo "Hero video start setting persistence and playback readiness smoke test passed.\n";
