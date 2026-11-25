<?php defined('TASKFLOW') or exit; ?>
<section class="auth-card">
    <h1>Connexion</h1>
    <p class="muted">Retrouve tes tableaux et tes tâches.</p>

    <?php require __DIR__ . '/../partials/errors.php'; ?>

    <form method="post" action="<?= e(url('login')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="email">
        </label>
        <label>Mot de passe
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn btn-primary" type="submit">Se connecter</button>
    </form>

    <p class="muted">Pas encore de compte ? <a class="link" href="<?= e(url('register')) ?>">Créer un compte</a></p>
</section>
