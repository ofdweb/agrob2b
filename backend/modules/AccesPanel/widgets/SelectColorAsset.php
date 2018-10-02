<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014
 * @package yii2-widgets
 * @subpackage yii2-widget-select2 
 * @version 1.0.0
 */

namespace backend\modules\CRM\widgets;

use Yii;
use yii\web\AssetBundle;
/**
 * Asset bundle for Select2 Widget
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class SelectColorAsset extends \kartik\base\AssetBundle
{

    public function init()
    {
        $this->setSourcePath(__DIR__.'/lib');
        $this->setupAssets('css', ['select']);
        $this->setupAssets('js', ['select.min','selectColor']);
        parent::init();
    }

}