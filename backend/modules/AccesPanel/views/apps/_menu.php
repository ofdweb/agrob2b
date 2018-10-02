<?php
    use yii\helpers\Html;
?>
       
    <p>
    <?= Html::a('К списку', ['/accespanel/apps/list'], ['class' => 'btn btn-success']) ?>
    <?php if(!$model->isNewRecord):?>
        <?= Html::a('Удалить приложение', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Вы действительно хотите удалить?',
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <p>
    <?php endif;?>
    </p>