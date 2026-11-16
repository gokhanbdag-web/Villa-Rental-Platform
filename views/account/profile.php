<?php
declare(strict_types=1);
$profileNotice = (string)($_SESSION['villa_profile_notice'] ?? '');
$profileError = (string)($_SESSION['villa_profile_error'] ?? '');
unset($_SESSION['villa_profile_notice'], $_SESSION['villa_profile_error']);
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hesap ayarlarım</title>
<style>
body{font-family:system-ui;background:#f6f8f6;color:#26332b;margin:0}.wrap{max-width:850px;margin:35px auto;padding:0 18px}.panel{background:#fff;border:1px solid #e7ece8;border-radius:14px;padding:22px;margin:16px 0}.panel label{display:block;font-size:13px;font-weight:700;margin:14px 0 6px}.panel input{box-sizing:border-box;width:100%;max-width:520px;padding:12px;border:1px solid #d7dfd9;border-radius:9px;font:inherit}.panel button{display:block;margin-top:18px;padding:12px 18px;border:0;border-radius:9px;background:var(--villa-primary,#176b50);color:white;font-weight:700;cursor:pointer}.muted{color:#68756c;font-size:13px}.notice,.error{padding:12px;border-radius:9px;margin:14px 0}.notice{background:#edf8f0;color:#176b50}.error{background:#fff0f0;color:#a22}
</style><?php require APP_ROOT . '/views/site/partials/account-theme.php'; ?></head>
<body><main class="wrap"><h1>Hesap ayarlarım</h1><p class="muted">Profil bilgilerinizi ve hesap güvenliğinizi buradan yönetin.</p>
<?php if($profileNotice!==''): ?><div class="notice"><?= e($profileNotice) ?></div><?php endif; ?>
<?php if($profileError!==''): ?><div class="error"><?= e($profileError) ?></div><?php endif; ?>
<section class="panel"><h2>Profil bilgileri</h2><form method="post" action="<?= e(app_url('/account/profile')) ?>"><?= csrf_field() ?>
<label for="username">Kullanıcı adı</label><input id="username" name="username" minlength="3" maxlength="80" required value="<?= e((string)$user['username']) ?>">
<label for="email">E-posta adresi</label><input id="email" type="email" value="<?= e((string)$user['email']) ?>" disabled><p class="muted">E-posta adresinizi değiştirmek için doğrulama bağlantısı gönderilir.</p>
<label for="new_email">Yeni e-posta adresi</label><input id="new_email" type="email" name="new_email" maxlength="190" autocomplete="email" placeholder="yeni@ornek.com">
<button type="submit">Profil bilgilerini kaydet</button></form></section>
<section class="panel"><h2>Şifre değiştir</h2><form method="post" action="<?= e(app_url('/account/password')) ?>"><?= csrf_field() ?>
<label for="current_password">Mevcut şifre</label><input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
<label for="new_password">Yeni şifre</label><input id="new_password" type="password" name="new_password" minlength="12" autocomplete="new-password" required>
<label for="new_password_confirm">Yeni şifre (tekrar)</label><input id="new_password_confirm" type="password" name="new_password_confirm" minlength="12" autocomplete="new-password" required>
<button type="submit">Şifreyi güncelle</button></form><p class="muted">Güvenlik için en az 12 karakter kullanın.</p></section>
<?php require APP_ROOT . '/views/site/partials/logout-button.php'; ?></main></body></html>
