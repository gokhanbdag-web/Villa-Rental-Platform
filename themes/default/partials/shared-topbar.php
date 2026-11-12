<?php
declare(strict_types=1);

$socialNames = [
    'instagram'=>'Instagram','facebook'=>'Facebook','youtube'=>'YouTube',
    'tiktok'=>'TikTok','x'=>'X','whatsapp'=>'WhatsApp','pinterest'=>'Pinterest',
    'telegram'=>'Telegram','linkedin'=>'LinkedIn',
];
$socialItems = '';
foreach ($socialNames as $platform => $label) {
    $item = $socialSettings[$platform] ?? [];
    $url = trim((string)($item['url'] ?? ''));
    if ($url === '') continue;
    $size = max(14, min(36, (int)($item['size'] ?? 20)));
    $family = (string)($item['family'] ?? 'brand');
    $socialItems .= '<a class="villa-social-link villa-social-link--' . e($family) . '" href="' . e($url) . '" target="_blank" rel="noopener noreferrer" aria-label="' . e($label) . '" title="' . e($label) . '" style="--social-icon-size:' . $size . 'px">' . $renderSocialIcon($platform, $family) . '</a>';
}
$addressSettings = $socialSettings['_address'] ?? [];
$addressText = trim((string)($addressSettings['text'] ?? ''));
$addressMarkup = $addressText !== '' ? '<div class="villa-social-address">' . e($addressText) . '</div>' : '';

$callCenterSettings = $socialSettings['_call_center'] ?? [];
$callCenterTitle = trim((string)($callCenterSettings['title'] ?? ''));
$callCenterPhone = trim((string)($callCenterSettings['phone'] ?? ''));
$callCenterDigits = preg_replace('/[^0-9+]/', '', $callCenterPhone) ?? '';
$callCenterTitleMarkup = $callCenterTitle !== '' ? '<span class="villa-social-call-label">' . e($callCenterTitle) . '</span>' : '';
$callCenterPhoneMarkup = $callCenterPhone !== '' && $callCenterDigits !== '' ? '<a href="tel:' . e($callCenterDigits) . '">' . e($callCenterPhone) . '</a>' : '';
$callCenterMarkup = ($callCenterTitleMarkup !== '' || $callCenterPhoneMarkup !== '') ? '<div class="villa-social-call-center">' . $callCenterTitleMarkup . $callCenterPhoneMarkup . '</div>' : '';

$licenseSettings = $socialSettings['_license'] ?? [];
$licenseTitle = trim((string)($licenseSettings['title'] ?? ''));
$licenseNumber = trim((string)($licenseSettings['number'] ?? ''));
$licenseTitleMarkup = $licenseTitle !== '' ? '<span class="villa-social-license-title">' . e($licenseTitle) . '</span>' : '';
$licenseNumberMarkup = $licenseNumber !== '' ? '<span class="villa-social-license-number">' . e($licenseNumber) . '</span>' : '';
$licenseSeparator = ($licenseTitleMarkup !== '' && $licenseNumberMarkup !== '') ? '<span class="villa-social-license-separator">:</span>' : '';
$licenseMarkup = ($licenseTitleMarkup !== '' || $licenseNumberMarkup !== '') ? '<div class="villa-social-license">' . $licenseTitleMarkup . $licenseSeparator . $licenseNumberMarkup . '</div>' : '';
?>
<?php if ($socialItems !== '' || $addressMarkup !== '' || $callCenterMarkup !== '' || $licenseMarkup !== ''): ?>
<div class="villa-social-bar">
  <div class="villa-social-inner">
    <div class="villa-social-left"><?= $socialItems ?></div>
    <?= $addressMarkup ?>
    <?= $callCenterMarkup ?>
    <?= $licenseMarkup !== '' ? $licenseMarkup : '<div class="villa-social-right"></div>' ?>
  </div>
</div>
<?php endif; ?>
