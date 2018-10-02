<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\Articles\models\Article */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Articles', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="article-view">

    <p>
        <a class="btn btn-info" href="/articles/">К списку</a>
        <?= Html::a('Править', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Подтверждаете удаление?',
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
			'author',
            'about:ntext',
            'text:ntext',
            'metaTitle:ntext',
            'metaKeywords:ntext',
            'metaDescription:ntext',
            'titleEn:ntext',
            'aboutEn:ntext',
            'textEn:ntext',
            'metaTitleEn:ntext',
            'metaKeywordsEn:ntext',
            'metaDescriptionEn:ntext',
			'dateCreate:datetime',
            'dateUpdate:datetime',
        ],
    ]) ?>

</div>
