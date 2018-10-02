<?php
namespace backend\modules\CRM\widgets;
use yii\helpers\Html;


class SelectColor extends \yii\bootstrap\Widget
{
    public $model;
    public $attribute;
    public $data;
    public $options = [];
    public $colors = [];
    public $ajaxValue = [];
    public $ajaxReload;        
    
    public function init(){
        $view = $this->getView();
        SelectColorAsset::register($view);
        parent::init();
    }

    public function run() {
        Html::addCssClass($this->options, 'form-control selectColor');
        Html::addCssStyle($this->options, 'width:100%', false);

        $view = $this->getView();
        SelectColorAsset::register($view);

        if(strstr($this->attribute,'[]')) $flag=true;
        $attribute=str_replace('[]','',$this->attribute);
        $selected=$this->model[$attribute];   
        
        $form=$flag?($this->model->formName().'['.$attribute.'][]'):$this->model->formName().'['.$attribute.']';                    

        if($this->colors){
            foreach($this->colors as $key=>$el)   $this->options['options'][$key]['color']=$el;
        }
        if($this->ajaxValue){
            foreach($this->ajaxValue as $key=>$el)   $this->options['options'][$key]['ajax-value']=$key;
        }
        
        if(!$this->options['data-target']) $this->options['data-target']='#addStatus';
        //var_dump($this->options);die;
        echo Html::dropDownList($form, $selected,$this->data,$this->options);

    }
}
