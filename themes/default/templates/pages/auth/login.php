<?php
declare(strict_types=1);
$errorKey = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?><!doctype html>
<html lang="<?= e(\App\Core\Translator::locale()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e(\App\Core\Translator::get('auth.login_title')) ?></title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f7f7f7;font-family:var(--villa-font-body);color:var(--villa-text);}.card{width:min(420px,calc(100% - 32px));padding:30px;background:#fff;border:1px solid #eee;border-radius:18px;box-shadow:0 10px 40px #0000000a}h1{font-size:var(--villa-body-size);margin:0 0 8px}p{color:var(--villa-text);margin:0 0 24px}label{display:block;font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);margin:14px 0 6px}input{width:100%;padding:13px;border:1px solid #ccc;border-radius:9px;font:inherit;}button{width:100%;border:0;border-radius:9px;padding:14px;background:#e65362;color:var(--villa-text);font-weight:var(--villa-body-weight);margin-top:20px;cursor:pointer}.error{background:#fff0f0;color:var(--villa-text);padding:10px;border-radius:8px;font-size:var(--villa-body-size);}
</style><?php require APP_ROOT . '/views/site/partials/account-theme.php'; ?></head><body><main class="card"><h1><?= e(\App\Core\Translator::get('auth.login_title')) ?></h1><p><?= e(\App\Core\Translator::get('auth.login_subtitle')) ?></p>
<?php if ($errorKey): ?><div class="error"><?= e(\App\Core\Translator::get($errorKey)) ?></div><?php endif; ?>
<form method="post" action="<?= e(app_url('/login')) ?>"><?= csrf_field() ?>
<label><?= e(\App\Core\Translator::get('auth.identity')) ?></label><input name="identity" autocomplete="username" required>
<label><?= e(\App\Core\Translator::get('auth.password')) ?></label><input name="password" type="password" autocomplete="current-password" required>
<button type="submit"><?= e(\App\Core\Translator::get('auth.sign_in')) ?></button></form><p style="margin:18px 0 0;font-size:var(--villa-body-size);"><?= e(villa_ui('Hesabınız yok mu?')) ?> <a href="<?= e(app_url('/register')) ?>"><?= e(villa_ui('Misafir hesabı oluşturun')) ?></a></p><p><a href="<?= e(app_url('/')) ?>"><?= e(villa_ui('Ana sayfaya dön')) ?></a></p></main></body></html>
