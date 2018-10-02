<?php
	use yii\helpers\Html;
    $this->title = Yii::t('app', 'Разрешение прав доступа');
    
    $url .= '&uid=' . $uidEncrypt;
?>
<br /><br />
<div class="panel panel-info">
  <div class="panel-heading"><?= Yii::t('app', 'Приложение <b>{app}</b> запрашивает доступ к вашим данным на Agro2b.com', ['app' => $app->appName]) ?></div>
  <div class="panel-body">
    <?php if (!Yii::$app->user->isGuest): ?>
        <h3><?= Yii::$app->user->identity->getFullName() ?> (<?= Yii::$app->user->identity->company->getCompanyName() ?>)</h3>
    <?php endif; ?>
    <ul>
        <?php foreach($app->permissios as $el): ?>
            <li><?= $el->permission->permissionText ?></li>
        <?php endforeach;?>
    </ul>
    
    <?php if (Yii::$app->user->isGuest): ?>
        <?= $this->render('@frontend/views/site/login', compact('model')) ?>
    <?php else: ?>
        <?= 
            Html::a(Yii::t('app', 'Разрешить'), $url, ['class' => 'btn btn-sm btn-success']) . ' ' . 
            Html::a(Yii::t('app', 'Отмена'), $url . '&cancel=1', ['class' => 'btn btn-sm btn-danger']) 
        ?>
    <?php endif; ?>
  </div>
</div>
