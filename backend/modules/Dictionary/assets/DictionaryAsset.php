<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace backend\modules\Dictionary\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DictionaryAsset extends AssetBundle
{
    public $sourcePath  = '@app/modules/Dictionary';
    public $css = [
        'assets/dictionary.css',
    ];
    public $js = [
        'assets/dictionary.js',     
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
