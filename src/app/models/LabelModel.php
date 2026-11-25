<?php
/**
 * Accès aux tables "labels" (étiquettes d'un tableau)
 * et "task_labels" (quelles étiquettes sont posées sur quelle tâche).
 */
defined('TASKFLOW') or exit;

function labels_for_board(int $boardId): array
{
    $stmt = db()->prepare('SELECT * FROM labels WHERE board_id = ? ORDER BY name');
    $stmt->execute([$boardId]);
    return $stmt->fetchAll();
}

/** Étiquettes de toutes les tâches d'un tableau : [task_id => [étiquettes…]] */
function labels_by_task(int $boardId): array
{
    $sql = 'SELECT tl.task_id, l.*
            FROM task_labels tl
            JOIN labels l ON l.id = tl.label_id
            WHERE l.board_id = ?
            ORDER BY l.name';
    $stmt = db()->prepare($sql);
    $stmt->execute([$boardId]);

    $grouped = [];
    foreach ($stmt->fetchAll() as $row) {
        $grouped[$row['task_id']][] = $row;
    }
    return $grouped;
}

/** Retrouve une étiquette, seulement si elle appartient à un tableau de l'utilisateur. */
function label_find(int $labelId, int $userId): ?array
{
    $sql = 'SELECT l.* FROM labels l
            JOIN boards b ON b.id = l.board_id
            WHERE l.id = ? AND b.user_id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$labelId, $userId]);
    return $stmt->fetch() ?: null;
}

function label_find_by_name(int $boardId, string $name): ?array
{
    $stmt = db()->prepare('SELECT * FROM labels WHERE board_id = ? AND name = ?');
    $stmt->execute([$boardId, $name]);
    return $stmt->fetch() ?: null;
}

function label_create(int $boardId, string $name, string $color): int
{
    $stmt = db()->prepare('INSERT INTO labels (board_id, name, color) VALUES (?, ?, ?)');
    $stmt->execute([$boardId, $name, $color]);
    return (int) db()->lastInsertId();
}

/** Supprime l'étiquette du tableau (elle disparaît aussi de toutes les tâches, grâce à ON DELETE CASCADE). */
function label_delete(int $labelId): void
{
    db()->prepare('DELETE FROM labels WHERE id = ?')->execute([$labelId]);
}

function task_has_label(int $taskId, int $labelId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM task_labels WHERE task_id = ? AND label_id = ?');
    $stmt->execute([$taskId, $labelId]);
    return (bool) $stmt->fetchColumn();
}

function task_add_label(int $taskId, int $labelId): void
{
    // INSERT IGNORE : si le lien existe déjà, MySQL ne fait rien (pas d'erreur de doublon)
    db()->prepare('INSERT IGNORE INTO task_labels (task_id, label_id) VALUES (?, ?)')->execute([$taskId, $labelId]);
}

function task_remove_label(int $taskId, int $labelId): void
{
    db()->prepare('DELETE FROM task_labels WHERE task_id = ? AND label_id = ?')->execute([$taskId, $labelId]);
}

/** Remplace toutes les étiquettes d'une tâche par la liste donnée (formulaire "Modifier"). */
function task_set_labels(int $taskId, array $labelIds): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM task_labels WHERE task_id = ?')->execute([$taskId]);
        $insert = $pdo->prepare('INSERT INTO task_labels (task_id, label_id) VALUES (?, ?)');
        foreach (array_unique($labelIds) as $labelId) {
            $insert->execute([$taskId, $labelId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
