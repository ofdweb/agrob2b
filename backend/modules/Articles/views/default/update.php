<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\modules\Articles\models\Article */

$this->title = 'Редактирование статьи: ' . ' ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Articles', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="article-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
