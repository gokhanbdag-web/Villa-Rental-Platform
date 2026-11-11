<?php
declare(strict_types=1);
$logoutUser = \App\Core\Auth::user();
if ($logoutUser):
?>
<form class="villa-account-logout" method="post" action="<?= e(app_url('/logout')) ?>">
<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
<button type="submit" class="villa-logout-button">Çıkış Yap</button>
</form>
<?php endif; ?>
