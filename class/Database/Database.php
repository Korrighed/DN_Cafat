<?php

namespace App\Database;

require_once __DIR__ . '/../../config/configDb.php';
/**
 * Gère la connexion à la base de données (pattern Singleton)
 */
class Database
{
    /** @var Database|null */
    private static $instance = null;


    /** @var PDO */
    private $pdo;

    /**
     * Constructeur privé - établit la connexion PDO
     * @throws \PDOException Si la connexion échoue
     */
    private function __construct()
    {
        $host = DB_HOST;
        $db   = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        $charset = DB_CHARSET;

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Retourne l'instance unique de Database
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne l'instance unique de Database
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }
}
