<?php
/**
 * Inscription, connexion, déconnexion.
 */
defined('TASKFLOW') or exit;

function auth_login(): void
{
    if (current_user()) {
        redirect('boards');
    }

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $user = user_find_by_email($email);

        // password_verify compare le mot de passe tapé avec le hash enregistré
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); // nouvel identifiant de session (évite le vol de session)
            $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
            flash('success', 'Content de te revoir, ' . $user['name'] . ' !');
            redirect('boards');
        }

        // Message volontairement vague : on ne dit pas si c'est l'email ou le mot de passe qui est faux
        $errors[] = 'Email ou mot de passe incorrect.';
    }

    render('auth/login', ['title' => 'Connexion', 'errors' => $errors]);
}

function auth_register(): void
{
    if (current_user()) {
        redirect('boards');
    }

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');

        if ($name === '' || mb_strlen($name) > 80) {
            $errors[] = 'Indique ton nom (80 caractères max).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        } elseif (user_find_by_email($email)) {
            $errors[] = 'Un compte existe déjà avec cet email.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Les deux mots de passe ne correspondent pas.';
        }

        if (!$errors) {
            $id = user_create($name, $email, $password);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $id, 'name' => $name, 'email' => $email];

            // Un premier tableau pour ne pas arriver sur une page vide
            board_create($id, 'Mon premier tableau', 'jaune');

            flash('success', 'Bienvenue sur TaskFlow, ' . $name . ' !');
            redirect('boards');
        }
    }

    render('auth/register', ['title' => 'Créer un compte', 'errors' => $errors]);
}

function auth_logout(): void
{
    $_SESSION = [];
    session_destroy();
    session_start();
    flash('info', 'Tu es déconnecté. À bientôt !');
    redirect('login');
}
