<?php defined('TASKFLOW') or exit; ?>
<div class="page-head">
    <div>
        <h1>Mes tableaux</h1>
        <p class="muted">Un tableau par projet. Clique dessus pour voir ses tâches.</p>
    </div>
</div>

<div class="boards-grid">
    <?php foreach ($boards as $board): ?>
        <?php $pct = $board['task_count'] ? round($board['done_count'] / $board['task_count'] * 100) : 0; ?>
        <article class="board-card bg-<?= e($board['color']) ?>">
            <a class="board-card-link" href="<?= e(url('board', ['id' => $board['id']])) ?>">
                <h2><?= e($board['title']) ?></h2>
                <p><?= (int) $board['task_count'] ?> tâche<?= $board['task_count'] > 1 ? 's' : '' ?> · <?= (int) $board['done_count'] ?> terminée<?= $board['done_count'] > 1 ? 's' : '' ?></p>
                <div class="progress" aria-label="<?= $pct ?> % terminé"><div style="width: <?= $pct ?>%"></div></div>
            </a>
            <form method="post" action="<?= e(url('board_delete')) ?>"
                  onsubmit="return confirm('Supprimer ce tableau et toutes ses tâches ?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $board['id'] ?>">
                <button class="icon-btn" type="submit" aria-label="Supprimer le tableau <?= e($board['title']) ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                </button>
            </form>
        </article>
    <?php endforeach; ?>

    <form class="board-card board-new" method="post" action="<?= e(url('board_create')) ?>">
        <?= csrf_field() ?>
        <h2>Nouveau tableau</h2>
        <label class="sr-only" for="board-title">Nom du tableau</label>
        <input id="board-title" type="text" name="title" placeholder="Ex. : Projet site web" required maxlength="120">
        <fieldset class="swatches">
            <legend class="sr-only">Couleur</legend>
            <?php foreach (palette() as $key => $label): ?>
                <label class="swatch bg-<?= e($key) ?>" title="<?= e($label) ?>">
                    <input type="radio" name="color" value="<?= e($key) ?>" <?= $key === 'jaune' ? 'checked' : '' ?>>
                    <span class="sr-only"><?= e($label) ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <button class="btn btn-primary" type="submit">+ Créer</button>
    </form>
</div>
