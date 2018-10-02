<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
		'css/daterangepicker.css',
    ];
    public $js = [	
		'js/plugins/moment.min.js',
		'js/plugins/daterangepicker.js',
		'js/plugins/jquery.maskedinput.js',
		'js/plugins/jquery.numberMask.js',
		'js/AdminLTE/app.js',	
		'js/bootstrap.min.js',
		'js/global.js',
    ];
    public $depends = [
		'yii\bootstrap\BootstrapAsset',
		'yii\bootstrap\BootstrapPluginAsset',
		'yii\web\YiiAsset',
    ];
}
