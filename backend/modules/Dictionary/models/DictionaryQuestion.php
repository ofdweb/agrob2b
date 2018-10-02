<?php

namespace backend\modules\Dictionary\models;

use Yii;

/**
 * This is the model class for table "b2b_DictionaryQuestion".
 *
 * @property integer $id
 * @property string $name
 * @property string $bodyText
 * @property string $email
 * @property integer $categoryId
 * @property string $dateAdd
 */
class DictionaryQuestion extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_DictionaryQuestion';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'bodyText', 'email'], 'required'],
            [['bodyText'], 'string'],
            [['categoryId'], 'integer'],
            [['dateAdd'], 'safe'],
            [['name', 'email'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Фамилия, имя',
            'bodyText' => 'Сообщение',
            'email' => 'E-mail получателя',
            'categoryId' => 'Категория вопроса',
            'dateAdd' => 'Дата создания',
        ];
    }
    
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        //$model=new Delivery();
        //$model->subject='Поступил вопрос по справочнику';
        //$model->text=$this->text;
    }
    
    public function getCategory()
	{
	   return $this->hasOne(DictionaryCategory::className(), ['id' => 'categoryId']);
	}
    
    public function gridProvider(){
        return DictionaryQuestion::find()->with('category');
    }
    
    public function oneById($id=null){
        return DictionaryQuestion::find()->where(['id'=>$id])->with('category')->one();
    }
    
    public function delOneById($id=null){
        DictionaryQuestion::deleteAll(['id'=>$id]);
    }
}
