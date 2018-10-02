<?php
namespace backend\modules\CRM\widgets;
use yii\helpers\Html;


class Calendar extends \yii\bootstrap\Widget
{
    public $model;
    public $attribute;
    public $data;
    public $options = [];    
    
    public function init(){
        parent::init();
    }

    public function run() {
        $view = $this->getView();
        CalendarAsset::register($view);
    }
}
