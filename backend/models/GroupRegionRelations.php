<?php

namespace backend\models;

use Yii;
use common\models\Address;
use common\models\Group;
/**
 * This is the model class for table "b2b_GroupRegionRelations".
 *
 * @property integer $companyId
 * @property integer $divisionId
 * @property integer $regionId
 * @property string $dateAdd
 * @property integer $userId
 */
class GroupRegionRelations extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_GroupRegionRelations';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['companyId', 'divisionId', 'regionId'], 'required'],
            [['companyId', 'divisionId', 'regionId', 'userId', 'type'], 'integer'],
            [['dateAdd'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'companyId' => Yii::t('app', 'Company ID'),
            'divisionId' => Yii::t('app', 'Division ID'),
            'regionId' => Yii::t('app', 'Region ID'),
            'dateAdd' => Yii::t('app', 'Date Add'),
            'type'=>Yii::t('app', 'Type'),
            'userId' => Yii::t('app', 'User ID'),
        ];
    }
    
    public function getAddress(){
        return $this->hasOne(Address::className(), ['id' => 'regionId'])->select(['id','name','socr','parentId']);
    }
    
    public function getRegions(){
        return $this->hasMany(GroupRegionRelations::className(), ['divisionId' => 'divisionId']);
    }
    
    public function getRegionChildsCount(){
        return $this->hasMany(Address::className(), ['parentId' => 'regionId']);
    }
    
    public function addItem($companyId=null,$divId=null,$type=0,$regionId=0){
        if($companyId && $divId && $type && $regionId){
            if(!GroupRegionRelations::oneById($companyId,$divId,$type,$regionId)){
                $model=new self([
                    'companyId'=>$companyId,
                    'divisionId'=>$divId,
                    'regionId'=>$regionId,
                    'type'=>$type,
                    'userId'=>isset(Yii::$app->user)?Yii::$app->user->id:0
                ]);
                if($model->save())  return  $model;   
            }
        }   
        return false;
    }

    public function delItem($companyId=null,$divId=null,$type=0,$regionId=0){
        if($companyId && $divId && $type && $regionId){
            if(GroupRegionRelations::deleteAll([
                'companyId'=>$companyId,
                'divisionId'=>$divId,
                'regionId'=>$regionId,
                'type'=>$type,
            ]))  return  true;
        } 
        return false;
    }
    
    public function changeItem($companyId=null,$divId=null,$type=0,$regionId=0){
        if($companyId && $divId && $type && $regionId){
            if(GroupRegionRelations::addItem($companyId,$divId,$type,$regionId)){
                return true;
            }
            else GroupRegionRelations::delItem($companyId,$divId,$type,$regionId);
        }   
        return false;
    }
    
    public function oneById($companyId=null,$divId=null,$type=0,$regionId=0){
        return GroupRegionRelations::find()->where([
            'companyId'=>$companyId,
            'divisionId'=>$divId,
            'regionId'=>$regionId,
            'type'=>$type
        ])->one();
    }
    
    public function lastRegions($companyId=0,$type=0){
        $model=GroupRegionRelations::find()->select(['divisionId','regionId'])->where(['companyId'=>$companyId,'type'=>$type])->orderBy(['dateAdd'=>SORT_DESC])->All();
        $result=[];

        if($model){
            $lastDivision=$model[0]->divisionId;
            foreach($model as $el){
                if($lastDivision!=$el->divisionId) return $result;
                $result[$el->regionId]=$el->divisionId;
            }
        }
        
        return $result;
    }
    
    public function listRegionsToPage($companyId=0,$type=0,$divisionId=0){
        return GroupRegionRelations::find()->where(['companyId'=>$companyId,'type'=>$type,'divisionId'=>$divisionId])->with('address','regionChildsCount')->groupBy(['regionId'])->asArray()->All();
    }
       
}
