<?php

namespace Hexlet\Code;

final class Database
{
    private static ?Database $connection = null;

    protected function __construct()
    {
    }

    public function connect()
    {
        $databaseUrl = parse_url((string) getenv('DATABASE_URL'));
        $username = $databaseUrl['user'];
        $password = $databaseUrl['pass'];
        $host = $databaseUrl['host'];
        $port = $databaseUrl['port'];
        $dbName = ltrim($databaseUrl['path'], '/');
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
          ];
        try {
            $pdo = new \PDO($dsn, $username, $password, $options);
            $migration = file_get_contents(__DIR__ . "/../database.sql");
            $statements = explode("\r\n\r\n", $migration);
            foreach ($statements as $statement) {
                $pdo->exec($statement);
            }
        } catch (\PDOException $e) {
            echo $e->getMessage();
            die();
        }
        return $pdo;
    }

    public static function get()
    {
        if (static::$connection === null) {
            static::$connection = new self();
        }

        return static::$connection;
    }
}
