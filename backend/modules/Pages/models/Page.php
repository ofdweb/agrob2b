<?php

namespace backend\modules\Pages\models;

use Yii;

use common\behaviors\DateTimeBehavior;
use common\behaviors\TranslateBehavior;

use backend\behaviors\UrlPageSaveBehavior;


/**
 * This is the model class for table "{{%Pages}}".
 *
 * @property integer $Id
 * @property string $Text
 * @property string $Url
 * @property string $MetaTitle
 * @property string $MetaKeywords
 * @property string $MetaDescription
 */
 

class Page extends \yii\db\ActiveRecord
{
	
	public function behaviors()
    {
        return [
            'DateTimeBehavior' => [
                'class' => DateTimeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['dateCreate', 'dateUpdate'],
                    \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'dateUpdate',
                ]
            ],
			'UrlPageSaveBehavior' => [
				'class' => UrlPageSaveBehavior::className()
			],
			'TranslateBehavior' => [
				'class' => TranslateBehavior::className(),
				'configurations' => [
					'LangFrom' => 'ru',
					'LangTo' => 'en',
					'Fields' => [
						'title' => 'titleEn',
						'text' => 'textEn',
						'metaTitle' => 'metaTitleEn',
						'metaKeywords' => 'metaKeywordsEn',
						'metaDescription' => 'metaDescriptionEn',
					]
				],
            ],	
        ];
	}
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%Pages}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title', 'text', 'url', 'metaTitle', 'metaKeywords', 'metaDescription', 'titleEn', 'textEn', 'metaTitleEn', 'metaKeywordsEn', 'metaDescriptionEn'], 'string'],
			[['url'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Заголовок',
            'url' => 'ЧПУ',
            'text' => 'Полный текст',
			'metaTitle' => 'Мета-заголовок',
			'metaKeywords' => 'Ключевые слова',
			'metaDescription' => 'Мета-описание',
			
			'titleEn' => 'Заголовок',
            'textEn' => 'Полный текст',
			'metaTitleEn' => 'Мета-заголовок',
			'metaKeywordsEn' => 'Ключевые слова',
			'metaDescriptionEn' => 'Мета-описание',
        ];
    }
}
