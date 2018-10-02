<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Управление приложениями';
$this->params['breadcrumbs'][] = $this->title;
?>
<div>
    <p>
        <?= Html::a('Добавить приложение', ['edit'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
		'filterModel' => $app,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'appName',
            'dateTo',
            '_key',
            ['class' => 'yii\grid\ActionColumn',
				'buttons' => [
					'update'=>function ($url, $data) {
                        $customurl=Yii::$app->getUrlManager()->createUrl(['/accespanel/apps/edit','id'=>$data['id']]);
                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $customurl,
						['title' => Yii::t('yii', 'Edit'), 'data-pjax' => '0']);
					},
					'delete'=>function ($url, $data) {
                        $customurl=Yii::$app->getUrlManager()->createUrl(['/accespanel/apps/delete','id'=>$data['id']]);
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $customurl,
						['title' => Yii::t('yii', 'Delete'), 'data-pjax' => '0']);
					}
				],
				'template' => '{update}&nbsp;{delete}'
			],
        ],
    ]); ?>
</div>
