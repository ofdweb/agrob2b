<?php

namespace backend\models;

use Yii;
use common\models\Okpd;
use common\models\Files;
use common\models\OkpdGraphs;
use common\models\Group;
/**
 * This is the model class for table "b2b_GroupOkpdRelations".
 *
 * @property integer $groupId
 * @property integer $okpdId
 * @property string $dateAdd
 */
class GroupOkpdRelations extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_GroupOkpdRelations';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['groupId', 'okpdId'], 'required'],
            [['groupId', 'okpdId','imageId'], 'integer'],
            [['dateAdd'], 'safe'],
            [['file'], 'file', 'extensions' => 'gif,png,jpg,jpg,jpeg'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'groupId' => 'Группа',
            'okpdId' => 'ОКВЭД',
            'dateAdd' => 'Добавлено',
            'imageId'=>'Изображение',
            'file'=>'Изображение'
        ];
    }
    
    public function beforeDelete()
    {
        $this->deleteImage();
        return parent::beforeDelete();
    }
    
    private function deleteImage($imageId=null){
        if ($model = Files::oneById($this->imageId)) {
            $model->delete();
        }
    }
    
    public $file;
    
    public static function primaryKey(){
        return array('okpdId');
    }
    
    public function getOkpd(){
        return $this->hasOne(Okpd::className(), ['id' => 'okpdId'])->select(['id','name','level','parentId']);
    }   
    
    public function getOkpdWithGraph(){
        return $this->hasOne(Okpd::className(), ['id' => 'okpdId'])->select(['id','name','level','parentId', 'code'])->with('graphs');
    }
    
    public function getOkpdGraph(){
        return $this->hasMany(OkpdGraphs::className(), ['parentId' => 'okpdId'])->with('okpdChild');
    }   
    
    public function getOkpdGraphWithoutChild(){
        return $this->hasMany(OkpdGraphs::className(), ['parentId' => 'okpdId']);
    }   
    
    public function getGroup(){
        return $this->hasOne(Group::className(), ['id' => 'groupId'])->where(['active'=>1]);
    } 
    
    public function getImage(){
        return $this->hasOne(Files::className(), ['id' => 'imageId']);
    } 
    
    public function itemListProvider($id=null){
        return GroupOkpdRelations::find()->where(['groupId'=>$id])->with('okpd','okpdGraph','image');
    }
    
    public function itemList($id=null){
        return GroupOkpdRelations::find()->select(['groupId','okpdId'])->where(['groupId'=>$id])->with('okpdGraph','okpd','group')->All();
    }
    
    public function allItemList(){
        return GroupOkpdRelations::find()->select(['okpdId'])->asArray()->All();
    }
    
    public function arrayListWithChilds(){
        $result=[];
        $itemList=GroupOkpdRelations::find()->select(['okpdId'])->with('okpdGraph')->asArray()->All();
        
        foreach($itemList as $el){
            $result[$el['okpdId']]=$el['okpdId'];
            if($el['okpdGraph']){
                foreach($el['okpdGraph'] as $e){
                    $result[$e['childId']]=$e['childId'];
                }
            }
        }
        
        return $result;
    }
    
    public function itemListGroup($groups=null){
        return GroupOkpdRelations::find()->select(['groupId','okpdId'])->where(['groupId'=>$groups])->All();
    }
    
    public function formattingByGroup($items=[]){
        $result=[];
        
        if($items){
            $i=0;
            foreach($items as $el){
                $result[$el->group->id]['group']=$el->group;
                $result[$el->group->id]['okpd'][$i]=$el;
                $i++;
            }
        }
        
        return $result;
    }
    
    public function addItem($groupId=null,$okpdId=null){
        if($groupId && $okpdId){
            if(!GroupOkpdRelations::oneById($groupId,$okpdId)){
                $model=new GroupOkpdRelations([
                    'groupId'=>$groupId,
                    'okpdId'=>$okpdId
                ]);
                if($model->save())  return  $model;
            }
            return true;
        }   
        return false;
    }
    
    public function delItem($groupId=null,$okpdId=null){
        if($groupId && $okpdId){
            if($model=GroupOkpdRelations::oneById($groupId,$okpdId)){
                $model->delete();
                return  true;
            }  
        } 
        return false;
    }
    
    public function oneById($groupId=null,$okpdId=null){
        return GroupOkpdRelations::find()->where(['groupId'=>$groupId,'okpdId'=>$okpdId])->one();
    }
    
    public function groupsByDivision($division=[]){
        $model=GroupOkpdRelations::find()->with('okpdGraph')->all();
        $result=[];

        if($model && $division){
            foreach($model as $elem){
                if(in_array($elem->okpdId,$division)){
                    $result[$elem->groupId]=$elem->groupId;
                    break;
                }
                if($elem->okpdGraph){
                    foreach($elem->okpdGraph as $el){
                        if(in_array($el->childId,$division)){
                            $result[$elem->groupId]=$elem->groupId;
                            break;
                        }
                    }    
                }
            }
        }
        return $result;
    }
}
