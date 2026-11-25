<?php
/**
 * TaskFlow — point d'entrée unique (front controller).
 *
 * Toutes les requêtes passent par ce fichier : index.php?page=xxx
 * On lit "page", puis on appelle la bonne fonction de contrôleur.
 */

define('TASKFLOW', true);

require __DIR__ . '/app/bootstrap.php';

$page   = $_GET['page'] ?? 'boards';
$method = $_SERVER['REQUEST_METHOD'];

// Table de routage : "page" => [fonction, méthode HTTP autorisée, connexion requise ?]
$routes = [
    // Authentification
    'login'        => ['auth_login',        'GET|POST', false],
    'register'     => ['auth_register',     'GET|POST', false],
    'logout'       => ['auth_logout',       'POST',     true],

    // Tableaux
    'boards'       => ['boards_index',      'GET',      true],
    'board'        => ['boards_show',       'GET',      true],
    'board_create' => ['boards_create',     'POST',     true],
    'board_delete' => ['boards_delete',     'POST',     true],

    // Tâches
    'task_create'  => ['tasks_create',      'POST',     true],
    'task_edit'    => ['tasks_edit',        'GET|POST', true],
    'task_delete'  => ['tasks_delete',      'POST',     true],
    'task_move'    => ['tasks_move',        'POST',     true], // appelé en JavaScript (fetch)
    'task_status'  => ['tasks_status',      'POST',     true], // menu ⋯ > « Marquer comme »

    // Étiquettes
    'label_toggle' => ['labels_toggle',     'POST',     true], // ajoute / retire une étiquette d'une tâche
    'label_create' => ['labels_create',     'POST',     true],
    'label_delete' => ['labels_delete',     'POST',     true],
];

if (!isset($routes[$page])) {
    http_response_code(404);
    render('errors/404', ['title' => 'Page introuvable']);
    exit;
}

[$handler, $allowed, $needsLogin] = $routes[$page];

if (!in_array($method, explode('|', $allowed), true)) {
    http_response_code(405);
    exit('Méthode non autorisée');
}

if ($needsLogin) {
    require_login();
}

// Protection CSRF sur tous les formulaires envoyés en POST
if ($method === 'POST') {
    csrf_check();
}

$handler();
