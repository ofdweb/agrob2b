<?php

namespace backend\models;

use Yii;
use common\behaviors\ActionLogsBehavior;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "b2b_TarifCompanyBlack".
 *
 * @property integer $id
 * @property integer $companyId
 * @property string $dateAdd
 */
class TarifCompanyBlack extends \yii\db\ActiveRecord
{
    public function behaviors()
    {
        return [
            'ActionLogsBehavior' => [
                'class' => ActionLogsBehavior::className(),
            ],
        ];
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_TarifCompanyBlack';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['companyId'], 'required'],
            [['companyId','userId'], 'integer'],
            [['dateAdd'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'companyId' => 'Компания',
            'dateAdd' => 'Добавлено',
            'userId'=>'Добавил'
        ];
    }
    
    public function listCompanyArray(){
        return ArrayHelper::getColumn(TarifCompanyBlack::find()->select('companyId')->all(),'companyId');
    }
    
    public function addCompany($cid=null){
        if($cid){
            $model=new TarifCompanyBlack([
                'companyId'=>$cid,
                'userId'=>Yii::$app->user->id
            ]);
            if($model->save())  return true;
        }
        return false;
    }
    
    public function delCompany($cid=null){
        if($cid){
            $model=TarifCompanyBlack::find()->where(['companyId'=>$cid])->one();
            if($model->delete())  return true;
        }
        return false;
    }
}
