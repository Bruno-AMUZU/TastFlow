<?php defined('TASKFLOW') or exit; $user = current_user(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'TaskFlow') ?> · TaskFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="page">
    <header class="topbar">
        <a class="brand" href="<?= e(url('boards')) ?>">
            <span class="brand-logo" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>
            </span>
            TaskFlow
        </a>

        <?php if ($user): ?>
            <div class="topbar-right">
                <a class="link" href="<?= e(url('boards')) ?>">Mes tableaux</a>
                <span class="avatar avatar-jaune" title="<?= e($user['name']) ?>"><?= e(initials($user['name'])) ?></span>
                <form method="post" action="<?= e(url('logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-ghost" type="submit">Déconnexion</button>
                </form>
            </div>
        <?php endif; ?>
    </header>

    <?php foreach (take_flashes() as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <main>
        <?= $content ?>
    </main>
</div>
<script src="assets/js/board.js" defer></script>
</body>
</html>
