<?php defined('TASKFLOW') or exit; ?>
<section class="auth-card">
    <h1>Créer un compte</h1>
    <p class="muted">Ça prend 20 secondes.</p>

    <?php require __DIR__ . '/../partials/errors.php'; ?>

    <form method="post" action="<?= e(url('register')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Nom
            <input type="text" name="name" value="<?= e(old('name')) ?>" required maxlength="80" autocomplete="name">
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
        </label>
        <label>Mot de passe <small>(8 caractères minimum)</small>
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <label>Confirmer le mot de passe
            <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
        </label>
        <button class="btn btn-primary" type="submit">Créer mon compte</button>
    </form>

    <p class="muted">Déjà inscrit ? <a class="link" href="<?= e(url('login')) ?>">Se connecter</a></p>
</section>
