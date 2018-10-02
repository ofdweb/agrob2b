<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\modules\Pages\models\Page */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="page-form">

	<?php $form = ActiveForm::begin(); ?>

    <div class="box box-success">
		<div class="box-header">
			<h3 class="box-title">Русский язык</h3>
		</div>
		<div class="box-body">
		
		
			<?= $form->field($model, 'title')->textInput() ?>
			<?
			
			$field = $form->field($model, 'url');
			//$form->field($model, 'username', ['options' => ['class' => 'form-group col-sm-6']]);
			$field->template = "{label}\n{input}\n{error}";  
			echo $field->textInput();
			
			?>

			<?= $form->field($model, 'text')->textarea(['rows' => 12]) ?>

			<?//$form->field($model, 'TitleEn')->textarea(['rows' => 6]) ?>
			
			<?= $form->field($model, 'metaTitle')->textInput() ?>
			<?= $form->field($model, 'metaKeywords')->textInput() ?>
			<?= $form->field($model, 'metaDescription')->textInput() ?>

		</div> 
		<div class="box-footer">
			Выводится на русскоязычной версии сайта
		</div> 
	</div>

		
	<div class="box box-success">
		<div class="box-header">
			<h3 class="box-title">Английский язык</h3>
		</div>
		<div class="box-body">
			<?= $form->field($model, 'titleEn')->textInput() ?>

			<?= $form->field($model, 'textEn')->textarea(['rows' => 12]) ?>

			<?//$form->field($model, 'TitleEn')->textarea(['rows' => 6]) ?>
			
			<?= $form->field($model, 'metaTitleEn')->textInput() ?>
			<?= $form->field($model, 'metaKeywordsEn')->textInput() ?>
			<?= $form->field($model, 'metaDescriptionEn')->textInput() ?>

		</div> 
		<div class="box-footer">
			Выводится на англоязычной версии сайта
		</div> 
	</div>
	
	
	<div class="box box-info">
		<div class="box-body">
			<div class="form-group">
				<a class="btn btn-info" href="/pages/">Отменить</a>
				<?= Html::submitButton($model->isNewRecord ? 'Создать' : 'Сохранить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
			</div>
		</div> 
	</div>


    

    <?php ActiveForm::end(); ?>

</div>
