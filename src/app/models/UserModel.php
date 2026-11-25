<?php
/**
 * Accès à la table "users".
 * Toutes les requêtes sont PRÉPARÉES (prepare + execute) : jamais de
 * variable collée directement dans le SQL => pas d'injection SQL.
 */
defined('TASKFLOW') or exit;

function user_find_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function user_create(string $name, string $email, string $password): int
{
    $stmt = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    return (int) db()->lastInsertId();
}
