<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\Group;
use common\components\THelper;
use \kartik\select2\Select2;
/* @var $this yii\web\View */
/* @var $model backend\models\Group */

$this->title = 'Настройка прав';
$this->params['breadcrumbs'][] = ['label' => 'Права', 'url' => ['/accespanel/apps/list']];
$this->params['breadcrumbs'][] = 'Управление правами';
?>
<div>
    <?= $this->render('_menu', ['model' => $model]) ?>
	
    <div>

    <?php $form = ActiveForm::begin(['options' => ['enctype'=>'multipart/form-data']]); ?>

		<?= $form->field($model, 'permissionText')->textInput(['maxlength' => true]) ?>
		<?= $form->field($model, 'permissionName')->textInput(['maxlength' => true]) ?>
		<?= $form->field($model, 'permissionValue')->textInput(['maxlength' => true]) ?>
		
		<div>
			<?= Html::submitButton($model->isNewRecord ? 'Создать' : 'Обновить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
		</div>

    <?php ActiveForm::end(); ?>

</div>
</div>
