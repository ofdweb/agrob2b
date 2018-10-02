<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title ='Просмотр справочника '. $model['name'];
$this->params['breadcrumbs'][] = ['label' => 'Система помощи и справки', 'url' => ['/dictionary/category']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="article-view">
    <div>
        <?= Html::a('Создать категорию', ['/dictionary/category/create'], ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Просмотр вопросов', ['/dictionary/question'], ['class' => 'btn btn-success']) ?>
        <?php if($model->id):?>
            <?= Html::a('Добавить подкатегорию.', ['/dictionary/category/create','parentId'=>$model->id], ['class' => 'btn btn-info']) ?>
            <?= Html::a('Править', ['/dictionary/category/update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Удалить', ['/dictionary/category/update', 'id' => $model->id,'del'=>1], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Подтверждаете удаление?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif;?>
    </div>
    <p></p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'parentId',
            [
                'attribute' => 'bodyText',
                'format' => 'raw',
            ], 
            [
                'attribute' => 'parentId',
                'format' => 'raw',
                'value'=>$model->category->name?$model->category->name:"-корень-"
            ],           
            'dateAdd',
            [
                'attribute' => 'creatorId',
                'format' => 'raw',
                'value'=>$model->creator->lastName.' '.$model->creator->name
            ],                 
        ],
    ]); ?>
</div>
