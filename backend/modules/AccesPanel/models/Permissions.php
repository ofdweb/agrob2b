<?php

namespace backend\modules\AccesPanel\models;

use Yii;

class Permissions extends \yii\db\ActiveRecord {
	
	public static function tableName() {
        return 'b2b_OauthPermission';
    }
	
	public function rules() {
        return [
            [['id'], 'integer'],
            [['permissionText','permissionName'], 'string', 'max' => 256],
			[['permissionValue'], 'integer']
        ];
    }
	
	public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'permissionText' => 'Описание доступа',
            'permissionName' => 'Название',
			'permissionValue' => 'Значение'
        ];
    }
	
}?>