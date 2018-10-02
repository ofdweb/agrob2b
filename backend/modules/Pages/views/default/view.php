<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\Pages\models\Page */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Pages', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-view">

    <p>
		<a class="btn btn-info" href="/pages/">К списку</a>
        <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'title:ntext',
            'url:ntext',
            'text:ntext',
            'metaTitle:ntext',
            'metaKeywords:ntext',
            'metaDescription:ntext',
            'titleEn:ntext',
            'textEn:ntext',
            'metaTitleEn:ntext',
            'metaKeywordsEn:ntext',
            'metaDescriptionEn:ntext',
			'dateCreate:datetime',
            'dateUpdate:datetime',
        ],
    ]) ?>

</div>
