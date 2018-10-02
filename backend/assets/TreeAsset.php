<?php

namespace backend\assets;

use yii\web\AssetBundle;

class TreeAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/plugins/jquery.multiselect.css',
        'css/plugins/jquery.multiselect.filter.css',
    ];
    public $js = [
        //'js/plugins/jquery.multiselect.js',
        //'js/plugins/jquery.multiselect.filter.js',
        //'js/tree.js',
		
    ];
    public $depends = [
        'yii\web\YiiAsset'
    ];
}
