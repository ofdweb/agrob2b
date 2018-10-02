<?php

namespace backend\modules\AccesPanel\models;

use Yii;

class PermissionApp extends \yii\db\ActiveRecord {
	
	public static function tableName() {
        return 'b2b_OauthPermissionApp';
    }
	
	public function rules() {
        return [
            [['idPermission', 'idApp'], 'integer']
        ];
    }
	
    public function getPermission()
    {
        return $this->hasOne(Permissions::className(), ['id' => 'idPermission']);
    }
    
    public function allByParams($params = [], $with = []) 
    {
        return PermissionApp::find()->where($params)->with($with)->all();
    }
}?>