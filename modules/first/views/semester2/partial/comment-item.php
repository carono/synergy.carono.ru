<?php
/**
 * @var Comment $model
 */

use app\models\Comment;
use carono\yii2rbac\RoleManager;
use yii\helpers\Html;

?>

<!-- Один комментарий -->
<div class="comment border rounded p-2 mb-2 d-flex justify-content-between align-items-start">
    <div>
        <div class="comment-meta text-muted" style="font-size: 0.85rem;">
            <?= Yii::$app->formatter->asDate($model->created_at) ?> — <strong><?= $model->user->username ?></strong>
        </div>
        <div class="comment-text">
            <?= $model->message ?>
        </div>
    </div>
    <?php
    if ($model->user_id == Yii::$app->user->id || RoleManager::haveRole('admin')) {
        echo Html::a('Удалить', ['/comment/delete', 'id' => $model->id],
            ['class' => 'btn btn-sm btn-danger ms-3', 'data-method' => 'post', 'data-pjax' => 1, 'data-confirm' => 'Удалить комментарий?']);
    }
    ?>
</div>