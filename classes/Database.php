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
        $dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD']);
        if (!is_null(self::$_db)) {
            return self::$_db;
        }
//        $serverName = filter_input(INPUT_SERVER, 'SERVER_NAME');
        $serverName = $_SERVER['SERVER_NAME'];
        $user = $_ENV['DB_USER'];
        $base = $_ENV['DB_NAME'];
        $password = $_ENV['DB_PASSWORD'];
        $socket = null;
        $port = null;
        switch ($serverName) {
            case 'localhost':
            case null:
                $server = "localhost";
                $socket = '/var/run/mysqld/mysqld.sock';
                $port = 3306;
                break;
            case 'www.ufolep13volley.org':
            case 'ufolep13volley.org':
            case '':
            case 'ufolepvocb.cluster020.hosting.ovh.net':
                $server = "ufolepvocbufolep.mysql.db";
                break;
            default:
                throw new Exception("Le nom du serveur nest pas autorisé pour la connexion SQL: $serverName");
        }
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
