<?php

namespace backend\modules\Dictionary\models;
use yii\helpers\Html;
use common\models\User;
use backend\modules\Dictionary\models\DictionaryQuestion;
use Yii;
/**
 * This is the model class for table "b2b_DictionaryCategory".
 *
 * @property integer $id
 * @property string $name
 * @property integer $parentId
 */
class DictionaryCategory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_DictionaryCategory';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['parentId', 'creatorId'], 'integer'],
            [['bodyText'], 'string'],
            [['dateAdd'], 'safe'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'parentId' => 'Родитель',
            'bodyText' => 'Описание',
            'dateAdd' => 'Дата создания',
            'creatorId' => 'Автор',
        ];
    }
    
    public function getCategory()
	{
	   return $this->hasOne(DictionaryCategory::className(), ['id' => 'parentId']);
	}
    
    public function getCreator()
	{
	   return $this->hasOne(User::className(), ['id' => 'creatorId']);
	}
    
    public function itemList(){
        return DictionaryCategory::find()->All();
    }
    
    public function listById($id=null){
        return DictionaryCategory::find()->where(['parentId'=>$id])->All();
    }
    
    public function oneById($id=null){
        return DictionaryCategory::find()->where(['id'=>$id])->with(['category','creator'])->one();
    }
    
    public function delOneById($id=null){
        DictionaryCategory::deleteAll(['id'=>$id]);
    }
    
    public function allParentsById($id=null){
        $model=DictionaryCategory::find()->All();
        $tree=DictionaryCategory::listParents($model,$id);
        return $tree;
    }
    
    public function listParents($tree, $parent = 0,$items=[]){
        foreach ($tree as $el) {
        if ($parent== $el->id) {
          $items[] = array(
            'name' => $el->name,
            'id' => $el->id,
          );
          $items+=DictionaryCategory::listParents($tree, $el->parentId,$items);
        }
      }
      return $items;
    }
    
    public function tree(){
        $model=DictionaryCategory::find()->All();
        return DictionaryCategory::listTree($model);
    }
    
    public function listTree($tree, $parent = 0) {
      $items = array();
     
      foreach ($tree as $el) {
        if ($parent== $el->parentId) {
          $items[] = array(
            'name' => $el->name,
            'id' => $el->id,
            'parentId' => $el->parentId,
            'children' => DictionaryCategory::listTree($tree, $el->id),
          );
        }
      }
      return $items;
    }
    
    public function printTree($tree,$elId=0,$html=''){     
        if(!is_null($tree) && count($tree) > 0) {
            $html.='<ul>';
            foreach($tree as $el){
                $html.='<li><div class="title"><div>';
                if($el['children']) $html.='<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>';
                $html.=Html::a($el['name'],[''],['class'=>'ajax-reload','ajax-reload'=>"productListGrid",'ajax-value'=>$el['id']]).'</span></div><span class="actions">'
                    .Html::a('<i class="fa fa-eye"></i>',['/dictionary/category/view','id'=>$el['id']])
                    .Html::a('<i class="fa fa-plus"></i>',['/dictionary/category/create','parentId'=>$el['id']])
                    .Html::a('<i class="fa fa-edit"></i>',['/dictionary/category/update','id'=>$el['id']])
                    .Html::a('<i class="fa fa-trash-o"></i>',['/dictionary/category/update','id'=>$el['id'],'del'=>1],['data' => [
                        'confirm' => 'Подтверждаете удаление?',
                        'method' => 'post',]]).'</div>'
                    .DictionaryCategory::printTree($el['children'],$el['id'])
                .'</li>'; 
            }
            $html.='</ul>';
        }
        return $html;
    }
    
    public function insertForm($model=null){
        $bodyText=$model->bodyText;
        $pattern ='/\{{(.+)\}}/';
        preg_match($pattern, $bodyText, $title);
        if($title[1]){
            $modelQuestion=new DictionaryQuestion();
            $modelQuestion->categoryId=$model->id;
            $bodyText=preg_replace($pattern,$this->renderPartial('questionForm',['title'=>$title[1],'model'=>$modelQuestion]),$bodyText);
        }
        return $bodyText;
    }
    
    public function searchByStr($str=null){
        return DictionaryCategory::find()->where(['like','bodyText',$str])->orWhere(['like','name',$str])->All();
    }
}
