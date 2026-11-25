<?php defined('TASKFLOW') or exit;
$colorKeys = array_keys(label_colors());
// Couleur proposée pour la prochaine étiquette créée : on tourne dans la palette
$nextColor = $colorKeys[count($labels) % count($colorKeys)];
?>
<div class="page-head">
    <div>
        <a class="link small" href="<?= e(url('boards')) ?>">← Mes tableaux</a>
        <h1><?= e($board['title']) ?></h1>
        <p class="muted">
            <?php if ($total === 0): ?>
                Aucune tâche pour l'instant. Ajoute la première !
            <?php else: ?>
                <?= $done ?> / <?= $total ?> tâche<?= $total > 1 ? 's' : '' ?> terminée<?= $done > 1 ? 's' : '' ?>.
                Glisse les cartes, ou utilise le menu ⋯ d'une carte.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="kanban" data-move-url="<?= e(url('task_move')) ?>">
    <?php foreach ($columns as $column): ?>
        <?php $columnTasks = $tasks[$column['id']] ?? []; ?>
        <section class="column bg-<?= e($column['color']) ?>">
            <header class="column-head">
                <h2><?= e($column['title']) ?></h2>
                <span class="count" data-count><?= count($columnTasks) ?></span>
            </header>

            <div class="card-list" data-column-id="<?= (int) $column['id'] ?>" data-done="<?= (int) $column['is_done'] ?>">
                <?php foreach ($columnTasks as $task): ?>
                    <?php
                    $myLabels   = $taskLabels[$task['id']] ?? [];
                    $myLabelIds = array_map('intval', array_column($myLabels, 'id'));
                    ?>
                    <article id="task-<?= (int) $task['id'] ?>" class="card<?= $column['is_done'] ? ' card-done' : '' ?>" draggable="true" data-task-id="<?= (int) $task['id'] ?>">
                        <div class="card-top">
                            <a class="card-link" draggable="false" href="<?= e(url('task_edit', ['id' => $task['id']])) ?>">
                                <span class="check" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span>
                                <h3><?= e($task['title']) ?></h3>
                            </a>

                            <?php /* ---------- Menu ⋯ ---------- */ ?>
                            <details class="card-menu">
                                <summary class="icon-btn icon-btn-sm" aria-label="Actions pour « <?= e($task['title']) ?> »">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
                                </summary>

                                <div class="menu-panel">
                                    <p class="menu-title">Marquer comme</p>
                                    <?php foreach ($columns as $target): ?>
                                        <?php $isCurrent = (int) $target['id'] === (int) $column['id']; ?>
                                        <form method="post" action="<?= e(url('task_status')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                            <input type="hidden" name="column_id" value="<?= (int) $target['id'] ?>">
                                            <button class="menu-item" type="submit" <?= $isCurrent ? 'disabled aria-current="true"' : '' ?>>
                                                <span class="dot bg-<?= e($target['color']) ?>"></span>
                                                <?= e($target['title']) ?>
                                                <?php if ($isCurrent): ?><span class="menu-hint">actuel</span><?php endif; ?>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>

                                    <p class="menu-title">Étiquettes</p>
                                    <div class="menu-labels">
                                        <?php if (!$labels): ?>
                                            <p class="menu-empty">Aucune étiquette pour l'instant. Crée la première ci-dessous (ex. : Urgent, Courses, Client…).</p>
                                        <?php endif; ?>
                                        <?php foreach ($labels as $label): ?>
                                            <?php $on = in_array((int) $label['id'], $myLabelIds, true); ?>
                                            <form method="post" action="<?= e(url('label_toggle')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                                <input type="hidden" name="label_id" value="<?= (int) $label['id'] ?>">
                                                <button class="label label-<?= e($label['color']) ?> label-toggle<?= $on ? ' is-on' : '' ?>" type="submit" aria-pressed="<?= $on ? 'true' : 'false' ?>"
                                                        title="<?= $on ? 'Retirer' : 'Ajouter' ?> « <?= e($label['name']) ?> »">
                                                    <?php if ($on): ?><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg><?php endif; ?>
                                                    <?= e($label['name']) ?>
                                                </button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                    <form class="menu-new-label" method="post" action="<?= e(url('label_create')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                        <input type="hidden" name="color" value="<?= e($nextColor) ?>">
                                        <label class="sr-only" for="new-label-<?= (int) $task['id'] ?>">Nouvelle étiquette</label>
                                        <input id="new-label-<?= (int) $task['id'] ?>" type="text" name="name" placeholder="Nouvelle étiquette" required maxlength="30">
                                        <button class="icon-btn icon-btn-sm" type="submit" aria-label="Créer l'étiquette">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </form>

                                    <hr>
                                    <a class="menu-item" href="<?= e(url('task_edit', ['id' => $task['id']])) ?>">Modifier la tâche…</a>
                                    <form method="post" action="<?= e(url('task_delete')) ?>"
                                          onsubmit="return confirm('Supprimer définitivement cette tâche ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                        <button class="menu-item menu-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </details>
                        </div>

                        <?php $late = is_late($task, $column); ?>
                        <?php if ($myLabels || $late || ($task['due_date'] && !$column['is_done']) || $task['author_name']): ?>
                            <div class="card-meta">
                                <?php foreach ($myLabels as $label): ?>
                                    <span class="label label-<?= e($label['color']) ?>"><?= e($label['name']) ?></span>
                                <?php endforeach; ?>
                                <?php if ($late): ?>
                                    <span class="label label-late">En retard</span>
                                <?php endif; ?>
                                <?php if ($task['due_date'] && !$column['is_done']): ?>
                                    <span class="due"><?= e(short_date($task['due_date'])) ?></span>
                                <?php endif; ?>
                                <?php if ($task['author_name']): ?>
                                    <span class="avatar avatar-sm avatar-jaune" title="<?= e($task['author_name']) ?>"><?= e(initials($task['author_name'])) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <form class="add-task" method="post" action="<?= e(url('task_create')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="column_id" value="<?= (int) $column['id'] ?>">
                <label class="sr-only" for="new-task-<?= (int) $column['id'] ?>">Nouvelle tâche dans <?= e($column['title']) ?></label>
                <input id="new-task-<?= (int) $column['id'] ?>" type="text" name="title" placeholder="+ Ajouter une tâche" required maxlength="160">
            </form>
        </section>
    <?php endforeach; ?>
</div>
