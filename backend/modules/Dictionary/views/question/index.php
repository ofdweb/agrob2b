<?php
    use yii\helpers\Html;
    use yii\helpers\Url;
    use yii\grid\GridView;  
?>
<div>
        <?= Html::a('Создать категорию', ['/dictionary/category/create'], ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Просмотр вопросов', ['/dictionary/question'], ['class' => 'btn btn-success']) ?>
    </div>
<p></p>
<?php yii\widgets\Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
		'layout'=>'{summary}{items}{pager}',
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
			'name',
            'email',
            'dateAdd',
            [
                'attribute' => 'categoryId',
                'format' => 'raw',
           	    'value' => function($data){return Html::a($data->category->name,['/dictionary/category/view','id'=>$data->categoryId]);},
			],
            [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {delete}',
                    'buttons' => [
					'delete' => function ($url, $model) {
						return Html::a('<span class="glyphicon glyphicon-trash"></span>', 
                            Yii::$app->UrlManager->createUrl(['/dictionary/question/delete','id'=>$model->id]), ['title' => 'Удалить','data' => [
                    'confirm' => 'Подтверждаете удаление?',
                    'method' => 'post',
                ],]);
					   },
                    'view'=>function ($url, $model) {
						return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', 
                            Yii::$app->UrlManager->createUrl(['/dictionary/question/view','id'=>$model->id]), ['title' => 'Просмотр',]);
					   }
				    ],
            ]
        ]
    ]); ?>
<?php yii\widgets\Pjax::end(); ?>