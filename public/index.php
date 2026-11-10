<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Installer;

if (!Installer::isInstalled()) {
    require dirname(__DIR__) . '/install/index.php';
    exit;
}

require dirname(__DIR__) . '/index.php';
