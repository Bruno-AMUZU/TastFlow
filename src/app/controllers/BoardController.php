<?php
/**
 * Tableaux : liste, affichage Kanban, création, suppression.
 */
defined('TASKFLOW') or exit;

function boards_index(): void
{
    render('boards/index', [
        'title'  => 'Mes tableaux',
        'boards' => board_all_for_user(user_id()),
    ]);
}

function boards_show(): void
{
    $board = board_find((int) ($_GET['id'] ?? 0), user_id());
    if (!$board) {
        http_response_code(404);
        render('errors/404', ['title' => 'Tableau introuvable']);
        return;
    }

    $columns = board_columns((int) $board['id']);
    $tasks   = tasks_by_column((int) $board['id']);

    $total = 0;
    $done  = 0;
    foreach ($columns as $column) {
        $count = count($tasks[$column['id']] ?? []);
        $total += $count;
        if ($column['is_done']) {
            $done += $count;
        }
    }

    render('boards/show', [
        'title'   => $board['title'],
        'board'   => $board,
        'columns' => $columns,
        'tasks'   => $tasks,
        'labels'  => labels_for_board((int) $board['id']),
        'taskLabels' => labels_by_task((int) $board['id']),
        'total'   => $total,
        'done'    => $done,
    ]);
}

function boards_create(): void
{
    $title = trim($_POST['title'] ?? '');
    $color = $_POST['color'] ?? 'jaune';

    if ($title === '' || mb_strlen($title) > 120) {
        flash('error', 'Donne un nom à ton tableau (120 caractères max).');
        redirect('boards');
    }
    if (!array_key_exists($color, palette())) {
        $color = 'jaune';
    }

    $id = board_create(user_id(), $title, $color);
    flash('success', 'Tableau « ' . $title . ' » créé.');
    redirect('board', ['id' => $id]);
}

function boards_delete(): void
{
    if (board_delete((int) ($_POST['id'] ?? 0), user_id())) {
        flash('success', 'Tableau supprimé.');
    }
    redirect('boards');
}
