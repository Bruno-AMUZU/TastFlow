<?php
/**
 * Chargement de l'application : session + tous les fichiers PHP utiles.
 */
defined('TASKFLOW') or exit; // empêche d'ouvrir ce fichier directement dans le navigateur

// Cookie de session plus sûr
session_set_cookie_params([
    'httponly' => true,       // inaccessible depuis JavaScript
    'samesite' => 'Lax',
]);
session_start();

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

require __DIR__ . '/models/UserModel.php';
require __DIR__ . '/models/BoardModel.php';
require __DIR__ . '/models/TaskModel.php';
require __DIR__ . '/models/LabelModel.php';

require __DIR__ . '/controllers/AuthController.php';
require __DIR__ . '/controllers/BoardController.php';
require __DIR__ . '/controllers/TaskController.php';
require __DIR__ . '/controllers/LabelController.php';
