<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title ='Просмотр вопроса '. $model['name'];
$this->params['breadcrumbs'][] = ['label' => 'Список вопросов', 'url' => ['/dictionary/question']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="article-view">
    <div>
        <?= Html::a('Создать категорию', ['/dictionary/category/create'], ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Просмотр вопросов', ['/dictionary/question'], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Удалить', ['/dictionary/question/delete','id'=>$model->id], ['class' => 'btn btn-danger','data' => [
            'confirm' => 'Подтверждаете удаление?',
            'method' => 'post',
        ],]) ?>
    </div>
    <p></p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'email', 
            [
                'attribute' => 'categoryId',
                'format' => 'raw',
                'value'=>Html::a($model->category->name,['/dictionary/category/view','id'=>$model->categoryId])
            ],           
            'dateAdd',
            'bodyText'                
        ],
    ]); ?>
</div>
