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
class OkpdTreeAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/plugins/chosen.min.css',
        'css/okpd-tree.css',
    ];
    public $js = [
        'js/plugins/jquery.chosen.js',
		'js/okpd-tree.js',
		
    ];
    public $depends = [
        'yii\web\YiiAsset'
    ];
}
