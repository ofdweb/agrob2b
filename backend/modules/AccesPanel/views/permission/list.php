<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Управление правами';
$this->params['breadcrumbs'][] = $this->title;
?>
<div>
    <p>
        <?= Html::a('Добавить право', ['edit'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
		'filterModel' => $premission,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'permissionText',
            'permissionName',
            'permissionValue',
            ['class' => 'yii\grid\ActionColumn',
				'buttons' => [
					'update'=>function ($url, $data) {
                        $customurl=Yii::$app->getUrlManager()->createUrl(['/accespanel/permission/edit','id'=>$data['id']]);
                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $customurl,
						['title' => Yii::t('yii', 'Edit'), 'data-pjax' => '0']);
					},
					'delete'=>function ($url, $data) {
                        $customurl=Yii::$app->getUrlManager()->createUrl(['/accespanel/permission/delete','id'=>$data['id']]);
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $customurl,
						['title' => Yii::t('yii', 'Delete'), 'data-pjax' => '0']);
					}
				],
				'template' => '{update}&nbsp;{delete}'
			],
        ],
    ]); ?>
</div>
