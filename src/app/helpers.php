<?php
/**
 * Petites fonctions utilisées partout dans l'application.
 */
defined('TASKFLOW') or exit;

/** Échappe un texte avant de l'afficher en HTML (protège contre les failles XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Construit une URL interne : url('board', ['id' => 3]) => index.php?page=board&id=3 */
function url(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

/** Redirige vers une page. $anchor permet de revenir pile sur un élément : 'task-12' => #task-12 */
function redirect(string $page, array $params = [], string $anchor = ''): never
{
    header('Location: ' . url($page, $params) . ($anchor !== '' ? '#' . $anchor : ''));
    exit;
}

/** Affiche une vue (fichier dans /views) à l'intérieur du gabarit layout.php. */
function render(string $view, array $data = []): void
{
    extract($data);                 // ['title' => 'x'] devient la variable $title
    ob_start();
    require __DIR__ . '/../views/' . $view . '.php';
    $content = ob_get_clean();
    require __DIR__ . '/../views/layout.php';
}

/** Renvoie une réponse JSON (pour les appels fetch en JavaScript). */
function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// --- Messages "flash" (affichés une seule fois après une redirection) ---

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// --- Anciennes valeurs d'un formulaire (pour ne pas tout retaper après une erreur) ---

function old(string $key, string $default = ''): string
{
    return (string) ($_POST[$key] ?? $default);
}

// --- Authentification ---

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function user_id(): int
{
    return (int) ($_SESSION['user']['id'] ?? 0);
}

function require_login(): void
{
    if (!current_user()) {
        flash('info', 'Connecte-toi pour accéder à tes tableaux.');
        redirect('login');
    }
}

/** Initiales d'un nom : "Bruno Amuzu" => "BA" */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $letters ?: '?';
}

// --- Protection CSRF ---
// Chaque formulaire contient un jeton secret ; on vérifie qu'il correspond
// à celui stocké en session. Un site pirate ne peut pas le deviner.

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session expirée : recharge la page et réessaie.');
    }
}

// --- Données d'affichage ---

/** Couleurs possibles pour une étiquette (classe CSS .label-xxx) */
function label_colors(): array
{
    return ['orange' => 'Orange', 'vert' => 'Vert', 'bleu' => 'Bleu', 'violet' => 'Violet', 'rose' => 'Rose', 'gris' => 'Gris'];
}

/** Couleurs possibles pour un tableau / une colonne */
function palette(): array
{
    return ['jaune' => 'Jaune', 'violet' => 'Violet', 'vert' => 'Vert', 'rose' => 'Rose', 'bleu' => 'Bleu'];
}

/** Date au format "28 nov." */
function short_date(?string $date): string
{
    if (!$date) {
        return '';
    }
    $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $ts = strtotime($date);
    return date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1];
}

/** Une tâche est en retard si sa date est passée et qu'elle n'est pas dans une colonne "Terminé". */
function is_late(array $task, array $column): bool
{
    return !$column['is_done'] && $task['due_date'] && $task['due_date'] < date('Y-m-d');
}
