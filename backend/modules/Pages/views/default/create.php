<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\modules\Pages\models\Page */

$this->title = 'Создание страницы';
$this->params['breadcrumbs'][] = ['label' => 'Pages', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
