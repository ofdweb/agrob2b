<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;
use yii\helpers\ArrayHelper;
use yii\grid\GridView;

use kartik\date\DatePicker;
use \kartik\select2\Select2;

use common\components\THelper;

use backend\modules\AccesPanel\assets\AccesPanelAsset;

AccesPanelAsset::register($this);

$this->title ='Создать токен';

?>
<div>

	<?= $this->render('_menu', ['model' => $model]) ?>

    <?php $form = ActiveForm::begin(['options' => ['enctype'=>'multipart/form-data']]); ?>
		
		<?= $form->field($modelUserList, 'id')->label('Пользователи')->widget(Select2::classname(), [
			'data' => $userList,
			'options' => ['multiple' => true]
		]); ?>
		
		<?= $form->field($modelAppList, 'id')->label('Приложение')->widget(Select2::classname(), [
			'data' => $appList,
			'value' => -1
		]); ?>
		
		<?= $form->field($modelPermissionList, 'id')->label('Права')->widget(Select2::classname(), [
			'data' => $permissionList,
			'options' => ['multiple' => true]
		]); ?>
		
		<div class="form-group">
			<?= HTML::label('Срок действия (дни)', '', ['class' => 'control-label']) ?>
			<?= HTML::input('text', 'dateTo', '', ['id' => 'app-dateto', 'class' => 'form-control']) ?>
		</div>
		
		<div>
			<?= Html::submitButton('Создать', ['class' => 'btn btn-success']) ?>
		</div>

    <?php ActiveForm::end(); ?>
</div>
