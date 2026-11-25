<?php defined('TASKFLOW') or exit; ?>
<section class="auth-card wide">
    <a class="link small" href="<?= e(url('board', ['id' => $task['board_id']])) ?>">← Retour au tableau</a>
    <h1>Modifier la tâche</h1>

    <?php require __DIR__ . '/../partials/errors.php'; ?>

    <form method="post" action="<?= e(url('task_edit', ['id' => $task['id']])) ?>" class="form">
        <?= csrf_field() ?>
        <label>Titre
            <input type="text" name="title" value="<?= e($task['title']) ?>" required maxlength="160">
        </label>

        <label>Description
            <textarea name="description" rows="4" placeholder="Détails, liens, étapes…"><?= e($task['description']) ?></textarea>
        </label>

        <div class="form-row">
            <label>Colonne
                <select name="column_id">
                    <?php foreach ($columns as $column): ?>
                        <option value="<?= (int) $column['id'] ?>" <?= (int) $column['id'] === (int) $task['column_id'] ? 'selected' : '' ?>><?= e($column['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Échéance
                <input type="date" name="due_date" value="<?= e($task['due_date']) ?>">
            </label>
        </div>

        <fieldset class="label-picker">
            <legend>Étiquettes <small>(clique pour cocher / décocher)</small></legend>
            <?php if (!$labels): ?>
                <p class="muted">Aucune étiquette dans ce tableau. Crée-en une juste en dessous.</p>
            <?php endif; ?>
            <?php foreach ($labels as $label): ?>
                <label class="label label-<?= e($label['color']) ?> label-check">
                    <input type="checkbox" name="labels[]" value="<?= (int) $label['id'] ?>" <?= in_array((int) $label['id'], $selected, true) ? 'checked' : '' ?>>
                    <?= e($label['name']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn btn-ghost" href="<?= e(url('board', ['id' => $task['board_id']])) ?>">Annuler</a>
        </div>
    </form>

    <?php /* Formulaires séparés : en HTML, on ne peut pas mettre un <form> dans un autre <form>. */ ?>
    <section class="label-manager">
        <h2>Étiquettes du tableau</h2>
        <p class="muted">Elles sont partagées par toutes les tâches de ce tableau.</p>

        <form class="label-create" method="post" action="<?= e(url('label_create')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
            <input type="hidden" name="return" value="edit">
            <label class="sr-only" for="label-name">Nom de la nouvelle étiquette</label>
            <input id="label-name" type="text" name="name" placeholder="Nouvelle étiquette (ex. : Urgent)" required maxlength="30">
            <label class="sr-only" for="label-color">Couleur</label>
            <select id="label-color" name="color">
                <?php foreach (label_colors() as $key => $name): ?>
                    <option value="<?= e($key) ?>"><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-ghost" type="submit">+ Créer</button>
        </form>

        <ul class="label-list">
            <?php foreach ($labels as $label): ?>
                <li>
                    <span class="label label-<?= e($label['color']) ?>"><?= e($label['name']) ?></span>
                    <form method="post" action="<?= e(url('label_delete')) ?>"
                          onsubmit="return confirm('Supprimer l\'étiquette « <?= e(addslashes($label['name'])) ?> » de tout le tableau ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="label_id" value="<?= (int) $label['id'] ?>">
                        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                        <input type="hidden" name="return" value="edit">
                        <button class="icon-btn icon-btn-sm" type="submit" aria-label="Supprimer l'étiquette <?= e($label['name']) ?> du tableau">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <form method="post" action="<?= e(url('task_delete')) ?>" class="danger-zone"
          onsubmit="return confirm('Supprimer définitivement cette tâche ?');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
        <button class="btn btn-danger" type="submit">Supprimer la tâche</button>
    </form>
</section>
