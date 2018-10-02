<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\CompanyContracts */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="company-contracts-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'transporterId')->textInput() ?>

    <?= $form->field($model, 'shipperId')->textInput() ?>

    <?= $form->field($model, 'num')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
