<?php

namespace backend\modules\AccesPanel\models;

use Yii;

class App extends \yii\db\ActiveRecord {
	
	public static function tableName() {
        return 'b2b_OauthApps';
    }
	
	public function rules() {
        return [
            [['appName'], 'string', 'max' => 64],
            [['_key', 'dateTo'], 'string', 'max' => 20]
        ];
    }
	
	public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'appName' => 'Название приложения',
            'dateTo' => 'Срок действия токена (дни)'  
        ];
    }
    
    public function getPermissios()
    {
        return $this->hasMany(PermissionApp::className(), ['idApp' => 'id'])->with('permission');
    }
	
	public static function generateRandomString($length = 20) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
	}
    
    public function existByParams($params = [])
    {
        return App::find()->where($params)->count();
    }
    
    public function oneWhithPermis($params = [])
    {
        return App::find()->where($params)->with('permissios')->one();
    }
	
}?>