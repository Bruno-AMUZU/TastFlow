<?php
/**
 * Tâches : création, modification, suppression, déplacement, changement de statut.
 */
defined('TASKFLOW') or exit;

function tasks_create(): void
{
    $column = column_find((int) ($_POST['column_id'] ?? 0), user_id());
    if (!$column) {
        http_response_code(404);
        exit('Colonne introuvable');
    }

    $title = trim($_POST['title'] ?? '');
    if ($title === '' || mb_strlen($title) > 160) {
        flash('error', 'Le titre de la tâche est obligatoire (160 caractères max).');
        redirect('board', ['id' => $column['board_id']]);
    }

    $id = task_create((int) $column['id'], $title, user_id());
    redirect('board', ['id' => $column['board_id']], 'task-' . $id);
}

function tasks_edit(): void
{
    $task = task_find((int) ($_GET['id'] ?? 0), user_id());
    if (!$task) {
        http_response_code(404);
        render('errors/404', ['title' => 'Tâche introuvable']);
        return;
    }

    $columns   = board_columns((int) $task['board_id']);
    $labels    = labels_for_board((int) $task['board_id']);
    $selected  = array_map('intval', array_column(labels_by_task((int) $task['board_id'])[$task['id']] ?? [], 'id'));
    $errors    = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'due_date'    => $_POST['due_date'] ?? '',
        ];
        $columnId = (int) ($_POST['column_id'] ?? $task['column_id']);

        // On ne garde que les étiquettes qui appartiennent bien à ce tableau
        $boardLabelIds = array_map('intval', array_column($labels, 'id'));
        $selected = array_values(array_intersect(
            array_map('intval', (array) ($_POST['labels'] ?? [])),
            $boardLabelIds
        ));

        if ($data['title'] === '' || mb_strlen($data['title']) > 160) {
            $errors[] = 'Le titre est obligatoire (160 caractères max).';
        }
        if ($data['due_date'] !== '' && !DateTime::createFromFormat('Y-m-d', $data['due_date'])) {
            $errors[] = 'Date invalide.';
        }
        if (!in_array($columnId, array_map('intval', array_column($columns, 'id')), true)) {
            $errors[] = 'Colonne invalide.';
        }

        if (!$errors) {
            task_update((int) $task['id'], $data);
            task_set_labels((int) $task['id'], $selected);
            if ($columnId !== (int) $task['column_id']) {
                task_move($task, $columnId, PHP_INT_MAX); // en bas de la nouvelle colonne
            }
            flash('success', 'Tâche enregistrée.');
            redirect('board', ['id' => $task['board_id']], 'task-' . $task['id']);
        }

        // On réaffiche le formulaire avec ce que l'utilisateur avait tapé
        $task = array_merge($task, $data, ['column_id' => $columnId]);
    }

    render('tasks/edit', [
        'title'    => 'Modifier la tâche',
        'task'     => $task,
        'columns'  => $columns,
        'labels'   => $labels,
        'selected' => $selected,
        'errors'   => $errors,
    ]);
}

function tasks_delete(): void
{
    $task = task_find((int) ($_POST['id'] ?? 0), user_id());
    if ($task) {
        task_delete((int) $task['id']);
        flash('success', 'Tâche « ' . $task['title'] . ' » supprimée.');
        redirect('board', ['id' => $task['board_id']]);
    }
    redirect('boards');
}

/** Appelé en JavaScript quand on glisse-dépose une carte. Répond en JSON. */
function tasks_move(): void
{
    $task   = task_find((int) ($_POST['task_id'] ?? 0), user_id());
    $column = column_find((int) ($_POST['column_id'] ?? 0), user_id());

    // La colonne d'arrivée doit être dans le même tableau que la tâche
    if (!$task || !$column || (int) $column['board_id'] !== (int) $task['board_id']) {
        json_response(['ok' => false, 'error' => 'Déplacement impossible'], 404);
    }

    task_move($task, (int) $column['id'], (int) ($_POST['index'] ?? 0));
    json_response(['ok' => true]);
}

/** Menu ⋯ > « Marquer comme … » : envoie la tâche en bas d'une autre colonne, sans glisser-déposer. */
function tasks_status(): void
{
    $task   = task_find((int) ($_POST['task_id'] ?? 0), user_id());
    $column = column_find((int) ($_POST['column_id'] ?? 0), user_id());

    if (!$task || !$column || (int) $column['board_id'] !== (int) $task['board_id']) {
        flash('error', 'Changement de statut impossible.');
        redirect('boards');
    }

    if ((int) $column['id'] !== (int) $task['column_id']) {
        task_move($task, (int) $column['id'], PHP_INT_MAX);
        flash('success', '« ' . $task['title'] . ' » → ' . $column['title']);
    }
    redirect('board', ['id' => $task['board_id']], 'task-' . $task['id']);
}
