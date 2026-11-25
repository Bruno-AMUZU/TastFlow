<?php
/**
 * Accès aux tables "boards" et "board_columns".
 * Chaque fonction filtre sur user_id : on ne peut voir QUE ses propres tableaux.
 */
defined('TASKFLOW') or exit;

/** Liste des tableaux d'un utilisateur, avec le nombre de tâches (total et terminées). */
function board_all_for_user(int $userId): array
{
    $sql = 'SELECT b.*,
                   COUNT(t.id)                          AS task_count,
                   COALESCE(SUM(c.is_done = 1 AND t.id IS NOT NULL), 0) AS done_count
            FROM boards b
            LEFT JOIN board_columns c ON c.board_id = b.id
            LEFT JOIN tasks t        ON t.column_id = c.id
            WHERE b.user_id = ?
            GROUP BY b.id
            ORDER BY b.created_at DESC, b.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function board_find(int $boardId, int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM boards WHERE id = ? AND user_id = ?');
    $stmt->execute([$boardId, $userId]);
    return $stmt->fetch() ?: null;
}

/** Crée un tableau avec ses 3 colonnes par défaut. Transaction = tout ou rien. */
function board_create(int $userId, string $title, string $color): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO boards (user_id, title, color) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $title, $color]);
        $boardId = (int) $pdo->lastInsertId();

        $columns = [
            ['À faire',  'jaune',  1, 0],
            ['En cours', 'violet', 2, 0],
            ['Terminé',  'vert',   3, 1],
        ];
        $stmt = $pdo->prepare('INSERT INTO board_columns (board_id, title, color, position, is_done) VALUES (?, ?, ?, ?, ?)');
        foreach ($columns as [$colTitle, $colColor, $position, $isDone]) {
            $stmt->execute([$boardId, $colTitle, $colColor, $position, $isDone]);
        }

        $pdo->commit();
        return $boardId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Supprime un tableau (ses colonnes et tâches partent avec, grâce à ON DELETE CASCADE). */
function board_delete(int $boardId, int $userId): bool
{
    $stmt = db()->prepare('DELETE FROM boards WHERE id = ? AND user_id = ?');
    $stmt->execute([$boardId, $userId]);
    return $stmt->rowCount() > 0;
}

function board_columns(int $boardId): array
{
    $stmt = db()->prepare('SELECT * FROM board_columns WHERE board_id = ? ORDER BY position, id');
    $stmt->execute([$boardId]);
    return $stmt->fetchAll();
}

/** Retrouve une colonne, seulement si elle appartient à un tableau de l'utilisateur. */
function column_find(int $columnId, int $userId): ?array
{
    $sql = 'SELECT c.* FROM board_columns c
            JOIN boards b ON b.id = c.board_id
            WHERE c.id = ? AND b.user_id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$columnId, $userId]);
    return $stmt->fetch() ?: null;
}
