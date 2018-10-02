<?php

namespace backend\models;

use Yii;
use common\models\User;
/**
 * This is the model class for table "b2b_TarifListUnVictory".
 *
 * @property integer $id
 * @property integer $lotId
 * @property integer $winId
 * @property integer $userId
 * @property string $dateAdd
 * @property integer $tarifId
 */
class TarifListUnVictory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_TarifListUnVictory';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lotId', 'winId', 'userId', 'tarifId'], 'required'],
            [['lotId', 'winId', 'userId', 'tarifId'], 'integer'],
            [['dateAdd'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('model', 'ID'),
            'lotId' => Yii::t('model', 'Лот'),
            'winId' => Yii::t('model', 'Победитель'),
            'userId' => Yii::t('model', 'Пользователь'),
            'dateAdd' => Yii::t('model', 'Дата добавления'),
            'tarifId' => Yii::t('model', 'Тариф'),
        ];
    }
    
    public function getUser(){
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }        
}
