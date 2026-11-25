<?php
/**
 * Connexion à MySQL avec PDO.
 *
 * Les identifiants viennent des variables d'environnement définies
 * dans compose.yaml (elles-mêmes lues depuis le fichier .env).
 */
defined('TASKFLOW') or exit;

function db(): PDO
{
    static $pdo = null;       // "static" : la connexion est créée une seule fois

    if ($pdo === null) {
        $host = getenv('MYSQL_HOST') ?: 'db';
        $port = getenv('MYSQL_PORT') ?: '3306';
        $name = getenv('MYSQL_DATABASE') ?: 'taskflow';
        $user = getenv('MYSQL_USER') ?: 'root';
        $pass = getenv('MYSQL_PASSWORD') ?: '';

        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erreurs SQL = exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // résultats en tableaux associatifs
            PDO::ATTR_EMULATE_PREPARES   => false,                  // vraies requêtes préparées
        ]);
    }

    return $pdo;
}
