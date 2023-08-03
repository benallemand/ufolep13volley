<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

class Database
{
    private static $_db = null;

    /**
     * @return false|mysqli|null
     * @throws Exception
     */
    public static function openDbConnection(): bool|mysqli|null
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        $dotenv->required([
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
            'DB_SERVER',
            'DB_SOCKET',
            'DB_PORT',
        ]);
        if (!is_null(self::$_db)) {
            return self::$_db;
        }
        $user = $_ENV['DB_USER'];
        $base = $_ENV['DB_NAME'];
        $password = $_ENV['DB_PASSWORD'];
        $server = $_ENV['DB_SERVER'];
        $socket = $_ENV['DB_SOCKET'];
        $port = $_ENV['DB_PORT'];
        self::$_db = mysqli_connect($server, $user, $password, $base, $port, $socket);
        if (self::$_db === false) {
            throw new Exception("Impossible de se connecter à la base de données !");
        }
        mysqli_set_charset(self::$_db, "utf8");
        mysqli_query(self::$_db, "SET lc_time_names = 'fr_FR'");
        mysqli_autocommit(self::$_db, true);
        return self::$_db;
    }

}
