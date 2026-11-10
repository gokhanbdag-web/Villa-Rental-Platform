<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Installer;

if (Installer::isInstalled()) {
    http_response_code(404);
    exit('Kurulum tamamlanmış.');
}
$error = null;
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        Installer::install($_POST);
        $success = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Villa Rental Platform - Kurulum</title>
<style>
:root{font-family:Inter,system-ui,sans-serif;color:#222}*{box-sizing:border-box}body{margin:0;background:#f7f7f7}.wrap{max-width:760px;margin:42px auto;padding:20px}.panel{background:#fff;border:1px solid #e8e8e8;border-radius:18px;padding:30px;box-shadow:0 8px 35px #00000008}h1{font-size:25px;margin:0 0 8px}.muted{color:#717171;margin:0 0 25px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}label{display:block;font-size:13px;font-weight:650;margin:0 0 6px}input,select{width:100%;padding:12px;border:1px solid #ccc;border-radius:9px;font:inherit}section{border-top:1px solid #eee;margin-top:24px;padding-top:20px}h2{font-size:16px;margin:0 0 15px}.field{margin-bottom:13px}.wide{grid-column:1/-1}button{background:#e65362;color:white;border:0;border-radius:9px;padding:14px 20px;font-weight:700;width:100%;cursor:pointer;margin-top:12px}.alert{padding:12px;border-radius:9px;background:#fff0f0;color:#a22;margin-bottom:15px}.ok{background:#effaf2;color:#176b35}@media(max-width:600px){.grid{grid-template-columns:1fr}.panel{padding:20px}}
</style></head><body><main class="wrap"><div class="panel">
<h1>Villa Rental Platform</h1><p class="muted">İlk kurulum sihirbazı</p>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert ok">Kurulum tamamlandı. Güvenlik için /install erişimi kilitlendi. <a href="../">Siteyi aç</a></div>
<?php else: ?>
<form method="post"><?= csrf_field() ?>
<section><h2>Veritabanı bağlantısı</h2><div class="grid">
<div class="field"><label>Sunucu</label><input name="db_host" value="localhost" required></div>
<div class="field"><label>Port</label><input name="db_port" type="number" value="3306" required></div>
<div class="field"><label>Veritabanı adı</label><input name="db_name" required></div>
<div class="field"><label>Veritabanı kullanıcı adı</label><input name="db_user" required></div>
<div class="field wide"><label>Veritabanı şifresi</label><input name="db_password" type="password" autocomplete="new-password"></div>
</div></section>
<section><h2>Yönetici hesabı</h2><div class="grid">
<div class="field"><label>Yönetici kullanıcı adı</label><input name="admin_username" minlength="3" required></div>
<div class="field"><label>Yönetici e-posta</label><input name="admin_email" type="email" required></div>
<div class="field"><label>Şifre (en az 12 karakter)</label><input name="admin_password" type="password" minlength="12" required></div>
<div class="field"><label>Şifre tekrarı</label><input name="admin_password_confirm" type="password" minlength="12" required></div>
</div></section>
<section><h2>İşletme modeli</h2><div class="field"><label>Platform çalışma şekli</label><select name="mode" required><option value="single_vendor">Single Vendor - Tek firma</option><option value="multi_vendor">Multi Vendor - Çok satıcılı portal</option></select></div></section>
<button type="submit">Kurulumu başlat</button></form>
<?php endif; ?></div></main></body></html>
