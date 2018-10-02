<?php
    use yii\helpers\Html;
?>
       
    <p>
    <?= Html::a('К списку', ['/accespanel/tokens/list'], ['class' => 'btn btn-success']) ?>
    <?php if(!$model->isNewRecord):?>
        <?= Html::a('Удалить токен', ['delete', 'id' => $model->id], [
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