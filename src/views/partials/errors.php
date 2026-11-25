<?php defined('TASKFLOW') or exit; ?>
<?php if (!empty($errors)): ?>
    <div class="flash flash-error" role="alert">
        <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
