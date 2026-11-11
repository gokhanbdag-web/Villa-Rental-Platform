<?php
$logoutUser = \App\Core\Auth::user();
if ($logoutUser):
?>
<style>
.villa-account-logout{display:flex;justify-content:flex-end;margin:22px 0}
.villa-account-logout button{border:1px solid #f0d9d6;border-radius:10px;background:#fff8f7;color:#a43e35;padding:10px 16px;font:inherit;font-weight:750;cursor:pointer}
.villa-account-logout button:hover{background:#fff0ee}
</style>
<form class="villa-account-logout" method="post" action="<?= e(app_url('/logout')) ?>">
<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
<button type="submit">Çıkış Yap</button>
</form>
<?php endif; ?>
