<?php

namespace backend\models;

use Yii;
use common\models\Company;
use common\models\Group;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "b2b_GroupCompanyRelations".
 *
 * @property integer $groupId
 * @property integer $companyId
 * @property string $dateAdd
 */
class GroupCompanyRelations extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_GroupCompanyRelations';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['groupId', 'companyId'], 'required'],
            [['groupId', 'companyId','offer','demand'], 'integer'],
            [['dateAdd'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'groupId' => 'Группа',
            'companyId' => 'Компания',
            'dateAdd' => 'Добавлено',
            'offer'=>'Предложение',
            'demand'=>'Спрос'    
        ];
    }
    
    public $typeList=[
        0=>'Системная',
        1=>'Продажа',
        2=>'Покупка'
    ];
    
    const DEFAULT_GROUP = 7; //группа по умолчанию
    const NZNP_GROUP = 8; //группа нзнп
    const INTOIL_GROUP = 17; //группа Intoil
    const SHIPPING = 16; //группа Shipping
    const MULTI_WIN = 13; //выбор нескольких победителей
    const S_H = 12;         //поставщики сельхозпродукции
    const CHANGE_VOLUME = 15;         //Настраиваемый объем при выборе победителя
    const TDNZNP = 18;         //TDNZNP
    const AGREEMENT = 19;         //TDNZNP
    const OIL = 11;
    const AUTO_LOGISTIK = 3;
    const TRAIN_LOGISTIK = 6;
    const SEA_LOGISTIK = 14;
    
    public static function primaryKey(){
       return array('companyId','groupId');
    }
    
    public function getCompany(){
        return $this->hasOne(Company::className(), ['id' => 'companyId']);
    }   
    
    public function getGroup(){
        return $this->hasOne(Group::className(), ['id' => 'groupId'])->where(['active'=>1]);
    }  
    
    public function itemListProvider($id=null){
        return GroupCompanyRelations::find()->where(['groupId'=>$id])->with('company')->orderBy(['dateAdd'=>SORT_DESC]);
    } 
    
    public function getGroupsOkpds()
    {
        return $this->hasMany(GroupOkpdRelations::className(), ['groupId' => 'groupId']);
    }
    
    public function getGroupsOkpdsWithOkpd()
    {
        return $this->hasMany(GroupOkpdRelations::className(), ['groupId' => 'groupId'])->with('okpdWithGraph');
    }
    
    public function groupListCompanyProvider($companyId=null){
        return GroupCompanyRelations::find()->where(['companyId'=>$companyId])->with('group')->orderBy(['dateAdd'=>SORT_DESC]);
    }
    
    public function groupListCompany($companyId=null){
        return GroupCompanyRelations::find()->where(['companyId'=>$companyId])->with('group')->All();
    }
    
    public function addItem($groupId=null,$companyId=null,$options=[]){
        if($groupId && $companyId){
            if(!GroupCompanyRelations::oneById($groupId,$companyId)){
                $model=new GroupCompanyRelations([
                    'groupId'=>$groupId,
                    'companyId'=>$companyId
                ]);
                
                if(isset($options['fields'])){
                    foreach($options['fields'] as $key=>$el){
                        $model->$key=$el;
                    }
                }
                
                if($model->save())  return  $model;    
            }
            return  true;   
        }   
        return false;
    }
    
    public function delItem($groupId=null,$companyId=null){
        if($groupId && $companyId){
            if(GroupCompanyRelations::deleteAll([
                'groupId'=>$groupId,
                'companyId'=>$companyId
            ]))  return  true;
        } 
        return false;
    }
    
    public function delAllByCompany($companyId=null){
        if($companyId){
            if(GroupCompanyRelations::deleteAll([
                'companyId'=>$companyId
            ]))  return  true;
        } 
        return false;
    }
    
    public function oneById($groupId=null,$companyId=null){
        return GroupCompanyRelations::find()->where(['groupId'=>$groupId,'companyId'=>$companyId])->one();
    }
    
    public function rewriteGroups($groupList=[],$companyId=0,$options=[]){
        if($companyId){ 
            GroupCompanyRelations::deleteAll([
                'AND', 'companyId = :companyId',
                ['NOT IN', 'groupId', $groupList]
            ], [':companyId' => $companyId]);
            
            if($groupList){
                $type=$options['type'];
                
                foreach($groupList as $el){
                    if(!$model=self::oneById($el,$companyId)){
                        $model=new GroupCompanyRelations([
                            'groupId'=>$el,
                            'companyId'=>$companyId
                        ]);
                    }
                    $model->{$type['field']}=$type['value'];
                    $model->save();
                }
            }
        }
    }
    
    public function addInDefaultGroup($companyId=null){
        if($companyId){
            $options['fields']=[
                'offer'=>1,
                'demand'=>1
            ];
            self::addItem(self::DEFAULT_GROUP,$companyId,$options);
        }
    }
    
    public function inGroup($groupId=0,$companyId=0,$options=[]){
        if(!$companyId) $companyId=Yii::$app->user->identity->companyId;
        
        $model=GroupCompanyRelations::find()->where(['groupId'=>$groupId,'companyId'=>$companyId]);
        
        if(isset($options['demand']))  $model->andWhere(['demand'=>$options['demand']]);
        if(isset($options['offer']))   $model->andWhere(['offer'=>$options['offer']]);
        
        return $model->count() ? true : false;
    }
    
    public function companyGroupList($companyId=0,$options=[]){
        if(!$companyId) $companyId=Yii::$app->user->identity->companyId;
        
        $model=GroupCompanyRelations::find()->where(['companyId'=>$companyId]);
        
        if(isset($options['demand']))  $model->andWhere(['demand'=>$options['demand']]);
        if(isset($options['offer']))   $model->andWhere(['offer'=>$options['offer']]);
        
        return ArrayHelper::getColumn($model->all(),'groupId');
    }
}
