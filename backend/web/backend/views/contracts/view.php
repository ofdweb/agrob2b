<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\CompanyContracts */

$this->title = $model->transporterId;
$this->params['breadcrumbs'][] = ['label' => 'Company Contracts', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="company-contracts-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'transporterId' => $model->transporterId, 'shipperId' => $model->shipperId], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'transporterId' => $model->transporterId, 'shipperId' => $model->shipperId], [
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
            'transporterId',
            'shipperId',
            'num',
        ],
    ]) ?>

</div>
