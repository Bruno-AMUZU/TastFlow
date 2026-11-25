<?php
/**
 * Accès à la table "tasks".
 */
defined('TASKFLOW') or exit;

/** Toutes les tâches d'un tableau, regroupées par colonne : [column_id => [tâches…]] */
function tasks_by_column(int $boardId): array
{
    $sql = 'SELECT t.*, u.name AS author_name
            FROM tasks t
            JOIN board_columns c ON c.id = t.column_id
            LEFT JOIN users u    ON u.id = t.created_by
            WHERE c.board_id = ?
            ORDER BY t.position, t.id';
    $stmt = db()->prepare($sql);
    $stmt->execute([$boardId]);

    $grouped = [];
    foreach ($stmt->fetchAll() as $task) {
        $grouped[$task['column_id']][] = $task;
    }
    return $grouped;
}

/** Retrouve une tâche, seulement si elle appartient à un tableau de l'utilisateur. */
function task_find(int $taskId, int $userId): ?array
{
    $sql = 'SELECT t.*, c.board_id
            FROM tasks t
            JOIN board_columns c ON c.id = t.column_id
            JOIN boards b        ON b.id = c.board_id
            WHERE t.id = ? AND b.user_id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$taskId, $userId]);
    return $stmt->fetch() ?: null;
}

/** Ajoute une tâche en bas de la colonne. */
function task_create(int $columnId, string $title, int $userId): int
{
    $stmt = db()->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM tasks WHERE column_id = ?');
    $stmt->execute([$columnId]);
    $position = (int) $stmt->fetchColumn();

    $stmt = db()->prepare('INSERT INTO tasks (column_id, title, position, created_by) VALUES (?, ?, ?, ?)');
    $stmt->execute([$columnId, $title, $position, $userId]);
    return (int) db()->lastInsertId();
}

function task_update(int $taskId, array $data): void
{
    $sql = 'UPDATE tasks SET title = ?, description = ?, due_date = ? WHERE id = ?';
    db()->prepare($sql)->execute([
        $data['title'],
        $data['description'] !== '' ? $data['description'] : null,
        $data['due_date'] !== '' ? $data['due_date'] : null,
        $taskId,
    ]);
}

function task_delete(int $taskId): void
{
    db()->prepare('DELETE FROM tasks WHERE id = ?')->execute([$taskId]);
}

/**
 * Déplace une tâche dans une colonne, à un rang donné (0 = tout en haut),
 * puis renumérote les positions de la colonne : 1, 2, 3…
 */
function task_move(array $task, int $toColumnId, int $index): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Les autres tâches de la colonne d'arrivée, dans l'ordre
        $stmt = $pdo->prepare('SELECT id FROM tasks WHERE column_id = ? AND id <> ? ORDER BY position, id');
        $stmt->execute([$toColumnId, $task['id']]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // On insère notre tâche au bon endroit
        $index = max(0, min($index, count($ids)));
        array_splice($ids, $index, 0, [$task['id']]);

        $update = $pdo->prepare('UPDATE tasks SET column_id = ?, position = ? WHERE id = ?');
        foreach ($ids as $i => $id) {
            $update->execute([$toColumnId, $i + 1, $id]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
