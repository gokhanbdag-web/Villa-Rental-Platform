<?php
declare(strict_types=1);
$accountBrandClass = 'theme-brand--coastal';
$accountHeaderClass = 'theme-header--coastal';
\App\\Core\\ThemeManager::renderPartial('partials/account-shell.php', get_defined_vars());
