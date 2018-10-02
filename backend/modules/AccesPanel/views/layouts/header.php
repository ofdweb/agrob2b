<?php 

use yii\helpers\Html;
use frontend\widgets\Alert;

use backend\modules\AccesPanel\assets\OauthAsset;
OauthAsset::register($this);
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">

<head>
	
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= $this->title ?></title>
    <?php $this->head() ?>	
	
</head>

<body>
    <?php $this->beginBody() ?>
    <?= Alert::widget(); ?>