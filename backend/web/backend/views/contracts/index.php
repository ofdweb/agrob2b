<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Company Contracts';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="company-contracts-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Company Contracts', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'transporterId',
            'shipperId',
            'num',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
