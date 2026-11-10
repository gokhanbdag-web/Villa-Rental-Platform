<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Installer;

if (!Installer::isInstalled()) {
    header('Location: ' . app_url('/install/'));
    exit;
}

require dirname(__DIR__) . '/index.php';
