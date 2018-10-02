<?php

use yii\helpers\Html;
use yii\grid\GridView;
use backend\modules\accespanel\models\Tokens;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Управление токенами';
$this->params['breadcrumbs'][] = $this->title;
?>
<div>
    <p>
        <?= Html::a('Cоздать токен', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
		'filterModel' => $token,
        'columns' => [
            'id',
            [
                'attribute' => 'token',
                'format' => 'raw',
                'value' => function($data) {
                    return '<div style="word-break: break-all;">' . $data->token . '</div>';
                }
            ],
            [
                'attribute' => 'idUser',
                'format' => 'raw',
                'value' => function($data) {
                    return Html::a($data->idUser, ['/users' , 'id' => $data->idUser], ['target' => '_blank']);
                }
            ],
            [
                'attribute' => 'idApp',
                'format' => 'raw',
                'value' => function($data) {
                    return Html::a($data->idApp, ['/accespanel/apps/edit' , 'id' => $data->idApp], ['target' => '_blank']);
                }
            ],
            [
                'attribute' => 'code',
                'format' => 'raw',
                'value' => function($data) {
                    return '<div style="word-break: break-all;">' . $data->code . '</div>';
                }
            ],
            [
                'attribute' => 'refreshToken',
                'format' => 'raw',
                'value' => function($data) {
                    return '<div style="word-break: break-all;">' . $data->refreshToken . '</div>';
                }
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function($data) {
                    return Tokens::$statusList[$data->status];
                }
            ],
            'dateTo',
            'dateCreate',
            ['class' => 'yii\grid\ActionColumn',
				'buttons' => [
					'delete'=>function ($url, $data) {
                        $customurl=Yii::$app->getUrlManager()->createUrl(['/accespanel/tokens/delete','id'=>$data['id']]);
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $customurl,
						['title' => Yii::t('yii', 'Delete'), 'data-pjax' => '0']);
					}
				],
				'template' => '{delete}'
			],
        ],
    ]); ?>
</div>
