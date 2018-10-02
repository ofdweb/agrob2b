<?php

namespace backend\models;

use Yii;
use common\models\User;
/**
 * This is the model class for table "b2b_IpBaning".
 *
 * @property integer $id
 * @property string $ip
 * @property integer $uid
 * @property string $dateAdd
 * @property string $baningTime
 */
class IpBaning extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_IpBaning';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ip'], 'required'],
            [['uid'], 'integer'],
            [['dateAdd', 'baningTime'], 'safe'],
            [['ip'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'IP',
            'uid' => 'Кто забанил',
            'dateAdd' => 'Дата бана',
            'baningTime' => 'Окончание бана',
        ];
    }
    
    public function afterSave($insert, $changedAttributes)
    {   
        if($insert) ActionLogs::create($this,'new',$changedAttributes);
        else ActionLogs::create($this,'edit',$changedAttributes); 
        
        parent::afterSave($insert, $changedAttributes);
    }
    
    public function getUser()
	{
		return $this->hasOne(User::className(), ['id' => 'uid']);
	}
    
    public function itemListProveder(){
        return IpBaning::find()->with('user')->orderBy(["id" => SORT_DESC ]);
    }
    
    public function itemList(){
        return IpBaning::find()->with('user')->orderBy(["id" => SORT_DESC ])->All();
    }
    
    public function oneByIp($ip=null){
        return IpBaning::find()->where(['ip'=>$ip])->one();
    }
    
    public function add($ip=null,$time='1 year'){
        $model=new IpBaning();
        $model->uid=Yii::$app->user->id;
        $model->ip=$ip;
        $model->baningTime=date('Y-m-d H:i:s',strtotime('+'.$time, time()));
        $model->save();
    }
    
    public function del($ip=null){
        IpBaning::deleteAll(['ip'=>$ip]);
    }
	
	
	/* использовать для хранения IP в базе */
	public static function convertIp($ip) {
		// по идее вот так
		//return ip2long($ip);
		
		return $ip;
	}
	
	/* использовать перед выводом IP */
	public static function deConvertIp($ip) {
		// по идее вот так
		//return long2ip($ip);
		
		return $ip;
	}
	
	/* возвращает проверка бана по текущему IP */
	public static function isBan() {		
		return self::find()->where(['ip' => self::convertIp(Yii::$app->request->userIp)])->count() > 0;
	}
}
