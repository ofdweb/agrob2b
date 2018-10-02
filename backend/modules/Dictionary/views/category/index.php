<?php
    use yii\helpers\Html;
    use yii\grid\GridView;
    
    use backend\modules\Dictionary\models\DictionaryCategory;
    
    use backend\modules\Dictionary\assets\DictionaryAsset;
    DictionaryAsset::register($this);
    
    $this->title = 'Система помощи и справки';
    $this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-index">
    <div>
        <?= Html::a('Создать категорию', ['/dictionary/category/create'], ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Просмотр вопросов', ['/dictionary/question'], ['class' => 'btn btn-success']) ?>
    </div>
    <p></p>
    <div id="DictionaryCategory">
        <?=DictionaryCategory::printTree($itemTree)?>
    </div>

</div>