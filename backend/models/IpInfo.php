<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "b2b_IpInfo".
 *
 * @property integer $id
 * @property string $hostName
 * @property integer $decimal
 * @property string $isp
 * @property string $organization
 * @property string $services
 * @property string $type
 * @property string $assignment
 * @property string $country
 * @property string $region
 * @property string $city
 * @property string $latitude
 * @property string $longitude
 * @property integer $postalCode
 * @property string $dateAdd
 */
class IpInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_IpInfo';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hostName', 'decimal', 'isp','ip'], 'required'],
            [['decimal', 'postalCode'], 'integer'],
            [['dateAdd'], 'safe'],
            [['hostName', 'isp','ip', 'organization', 'services', 'type', 'assignment', 'country', 'region', 'city', 'latitude', 'longitude'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip'=>'IP адрес',
            'hostName' => 'Хост',
            'decimal' => 'Десятичный',
            'isp' => 'ISP',
            'organization' => 'Организация',
            'services' => 'Сервисы',
            'type' => 'Тип',
            'assignment' => 'Назначение',
            'country' => 'Страна',
            'region' => 'Регион',
            'city' => 'Город',
            'latitude' => 'Широта',
            'longitude' => 'Долгота',
            'postalCode' => 'Почтовый индекс',
            'dateAdd' => 'Дата добавления',
        ];
    }
    
    public function oneByIp($ip=null){
        return IpInfo::find()->where(['ip'=>$ip])->one();
    }
}
