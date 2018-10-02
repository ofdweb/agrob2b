<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\modules\Pages\models\Editor */

$this->title = 'Create Editor';
$this->params['breadcrumbs'][] = ['label' => 'Editors', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="editor-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
