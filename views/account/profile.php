<?php
declare(strict_types=1);
$profileNotice = (string)($_SESSION['villa_profile_notice'] ?? '');
$profileError = (string)($_SESSION['villa_profile_error'] ?? '');
unset($_SESSION['villa_profile_notice'], $_SESSION['villa_profile_error']);
?><!doctype html>
<html lang="<?= e(\App\Core\Translator::locale()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e(\App\Core\Translator::get('account.profile.title')) ?></title>
<style>
body{font-family:system-ui;background:#f6f8f6;color:#26332b;margin:0}.wrap{max-width:850px;margin:35px auto;padding:0 18px}.panel{background:#fff;border:1px solid #e7ece8;border-radius:14px;padding:22px;margin:16px 0}.panel label{display:block;font-size:13px;font-weight:700;margin:14px 0 6px}.panel input{box-sizing:border-box;width:100%;max-width:520px;padding:12px;border:1px solid #d7dfd9;border-radius:9px;font:inherit}.panel button{display:block;margin-top:18px;padding:12px 18px;border:0;border-radius:9px;background:var(--villa-primary,#176b50);color:white;font-weight:700;cursor:pointer}.muted{color:#68756c;font-size:13px}.notice,.error{padding:12px;border-radius:9px;margin:14px 0}.notice{background:#edf8f0;color:#176b50}.error{background:#fff0f0;color:#a22}
</style><?php require APP_ROOT . '/views/site/partials/account-theme.php'; ?></head>
<body><main class="wrap"><h1><?= e(\App\Core\Translator::get('account.profile.title')) ?></h1><p class="muted"><?= e(\App\Core\Translator::get('account.profile.intro')) ?></p>
<?php if($profileNotice!==''): ?><div class="notice"><?= e($profileNotice) ?></div><?php endif; ?>
<?php if($profileError!==''): ?><div class="error"><?= e($profileError) ?></div><?php endif; ?>
<section class="panel"><h2><?= e(\App\Core\Translator::get('account.profile.details')) ?></h2><form method="post" action="<?= e(app_url('/account/profile')) ?>"><?= csrf_field() ?>
<label for="username"><?= e(\App\Core\Translator::get('account.profile.username')) ?></label><input id="username" name="username" minlength="3" maxlength="80" required value="<?= e((string)$user['username']) ?>">
<label for="email"><?= e(\App\Core\Translator::get('account.profile.email')) ?></label><input id="email" type="email" value="<?= e((string)$user['email']) ?>" disabled><p class="muted"><?= e(\App\Core\Translator::get('account.profile.email_help')) ?></p>
<label for="new_email"><?= e(\App\Core\Translator::get('account.profile.new_email')) ?></label><input id="new_email" type="email" name="new_email" maxlength="190" autocomplete="email" placeholder="yeni@ornek.com">
<button type="submit"><?= e(\App\Core\Translator::get('account.profile.details')) ?>ni kaydet</button></form></section>
<section class="panel"><h2><?= e(\App\Core\Translator::get('account.profile.change_password')) ?></h2><form method="post" action="<?= e(app_url('/account/password')) ?>"><?= csrf_field() ?>
<label for="current_password"><?= e(\App\Core\Translator::get('account.profile.current_password')) ?></label><input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
<label for="new_password"><?= e(\App\Core\Translator::get('account.profile.new_password')) ?></label><input id="new_password" type="password" name="new_password" minlength="12" autocomplete="new-password" required>
<label for="new_password_confirm"><?= e(\App\Core\Translator::get('account.profile.confirm_password')) ?></label><input id="new_password_confirm" type="password" name="new_password_confirm" minlength="12" autocomplete="new-password" required>
<button type="submit"><?= e(\App\Core\Translator::get('account.profile.update_password')) ?></button></form><p class="muted"><?= e(\App\Core\Translator::get('account.profile.password_help')) ?></p></section>
<?php require APP_ROOT . '/views/site/partials/logout-button.php'; ?></main></body></html>
