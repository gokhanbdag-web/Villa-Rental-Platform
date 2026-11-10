<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;
    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) return self::$connection;
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        self::$connection = Installer::connect($config['database']);
        return self::$connection;
    }
}
