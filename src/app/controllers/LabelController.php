<?php
/**
 * Étiquettes : poser / retirer sur une tâche, créer, supprimer.
 *
 * Après chaque action, on revient là où l'utilisateur était :
 * - champ "return" = "edit"  => page « Modifier la tâche »
 * - sinon                    => tableau, positionné sur la carte (#task-12)
 */
defined('TASKFLOW') or exit;

function back_to_task(array $task): never
{
    if (($_POST['return'] ?? '') === 'edit') {
        redirect('task_edit', ['id' => $task['id']]);
    }
    redirect('board', ['id' => $task['board_id']], 'task-' . $task['id']);
}

/** Menu ⋯ : clic sur une étiquette => on la pose si elle n'y est pas, on la retire sinon. */
function labels_toggle(): void
{
    $task  = task_find((int) ($_POST['task_id'] ?? 0), user_id());
    $label = label_find((int) ($_POST['label_id'] ?? 0), user_id());

    if (!$task || !$label || (int) $label['board_id'] !== (int) $task['board_id']) {
        flash('error', 'Étiquette introuvable.');
        redirect('boards');
    }

    if (task_has_label((int) $task['id'], (int) $label['id'])) {
        task_remove_label((int) $task['id'], (int) $label['id']);
    } else {
        task_add_label((int) $task['id'], (int) $label['id']);
    }
    back_to_task($task);
}

/** Crée une étiquette dans le tableau de la tâche, et la pose directement sur la tâche. */
function labels_create(): void
{
    $task = task_find((int) ($_POST['task_id'] ?? 0), user_id());
    if (!$task) {
        flash('error', 'Tâche introuvable.');
        redirect('boards');
    }

    $name  = trim($_POST['name'] ?? '');
    $color = $_POST['color'] ?? 'gris';
    if (!array_key_exists($color, label_colors())) {
        $color = 'gris';
    }

    if ($name === '' || mb_strlen($name) > 30) {
        flash('error', "Le nom de l'étiquette doit faire entre 1 et 30 caractères.");
        back_to_task($task);
    }

    // Si elle existe déjà dans ce tableau, on la réutilise au lieu de créer un doublon
    $existing = label_find_by_name((int) $task['board_id'], $name);
    $labelId  = $existing ? (int) $existing['id'] : label_create((int) $task['board_id'], $name, $color);

    task_add_label((int) $task['id'], $labelId);
    back_to_task($task);
}

/** Supprime l'étiquette du tableau entier (et donc de toutes ses tâches). */
function labels_delete(): void
{
    $label = label_find((int) ($_POST['label_id'] ?? 0), user_id());
    $task  = task_find((int) ($_POST['task_id'] ?? 0), user_id());

    if ($label) {
        label_delete((int) $label['id']);
        flash('success', 'Étiquette « ' . $label['name'] . ' » supprimée du tableau.');
    }

    if ($task) {
        back_to_task($task);
    }
    redirect('board', ['id' => $label['board_id'] ?? 0]);
}
