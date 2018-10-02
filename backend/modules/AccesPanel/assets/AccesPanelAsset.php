<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace backend\modules\AccesPanel\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AccesPanelAsset extends AssetBundle
{
    public $sourcePath = '@app/modules/AccesPanel';
    public $css = [
        'assets/AccesPanel.css',
    ];
    public $js = [
        'assets/AccesPanel.js'    
    ];
	
    public $depends = [
		'yii\web\YiiAsset',
		'yii\web\JqueryAsset',
		'yii\bootstrap\BootstrapAsset',
		'kartik\select2\Select2Asset',
		
    ];
}
