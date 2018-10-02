<?php

    use yii\helpers\Html;
    use yii\widgets\ActiveForm;
    use \kartik\select2\Select2;
    use dosamigos\ckeditor\CKEditor;
?>
<div class="category">
    <div>
        <?= Html::a('Создать категорию', ['/dictionary/category/create'], ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Просмотр вопросов', ['/dictionary/question'], ['class' => 'btn btn-success']) ?>
        <?php if($model->id):?>
            <?= Html::a('Добавить подкатегорию.', ['/dictionary/category/create','parentId'=>$model->id], ['class' => 'btn btn-info']) ?>
            <?= Html::a('Просмотр', ['/dictionary/category/view', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Удалить', ['/dictionary/category/update', 'id' => $model->id,'del'=>1], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Подтверждаете удаление?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif;?>
    </div>
    <p></p>
    <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'name') ?>
        <?= $form->field($model, 'bodyText')->widget(CKEditor::className(), [
            'options' => ['rows' => 6],
            'preset' => 'full'
        ]) ?>    
        
        <p>Для добавления формы "Задать вопрос" используйте конструкцию {{Название ссылки}}, где текст "Название ссылки" может быть любым</p>
          
        <?=$form->field($model, 'parentId')->widget(Select2::classname(), [
            'data' => $parentList,
        ])?>
        <?= $form->field($model, 'creatorId')->hiddenInput()->label(false) ?>
    
        <div class="form-group">
            <?= Html::submitButton($model->id?'Сохранить':'Создать', ['class' => 'btn btn-primary']) ?>
        </div>
    <?php ActiveForm::end(); ?>

</div>