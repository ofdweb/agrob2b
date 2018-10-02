<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\CompanyContracts */

$this->title = 'Update Company Contracts: ' . ' ' . $model->transporterId;
$this->params['breadcrumbs'][] = ['label' => 'Company Contracts', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->transporterId, 'url' => ['view', 'transporterId' => $model->transporterId, 'shipperId' => $model->shipperId]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="company-contracts-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
