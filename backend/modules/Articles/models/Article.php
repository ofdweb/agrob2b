<?php

namespace backend\modules\Articles\models;

use Yii;
use yii\behaviors\AttributeBehavior;

use common\behaviors\DateTimeBehavior;
use common\behaviors\TranslateBehavior;



/**
 * This is the model class for table "{{%Articles}}".
 *
 * @property integer $id
 * @property string $Title
 * @property string $About
 * @property string $Text
 * @property integer $Author
 * @property string $TitleEn
 * @property string $Code
 */
class Article extends \yii\db\ActiveRecord
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
            'AttributeBehavior' => [
			   'class' => AttributeBehavior::className(),
			   'attributes' => [
				   \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'author',
				   \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'author',
				],
				'value' => function ($event) {
				   return Yii::$app->user->identity->UserName;
				},
            ],
            'TranslateBehavior' => [
				'class' => TranslateBehavior::className(),
				'configurations' => [
					'LangFrom' => 'ru',
					'LangTo' => 'en',
					'Fields' => [
						'title' => 'titleEn',
						'text' => 'textEn',
						'about' => 'aboutEn',
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
        return '{{%Articles}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[['Title', 'TitleEn'], 'yii\validators\UniqueorValidator'],
            [['title'], 'required'],
            [['title', 'about', 'text', 'titleEn', 'url', 'author', 'metaTitle', 'metaKeywords', 'metaDescription', 'aboutEn', 'textEn', 'metaTitleEn', 'metaKeywordsEn', 'metaDescriptionEn'], 'string'],
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
            'about' => 'Анонс',
            'text' => 'Полный текст',
            'author' => 'Автор',
			'metaTitle' => 'Мета-заголовок',
			'metaKeywords' => 'Ключевые слова',
			'metaDescription' => 'Мета-описание',
			
			'titleEn' => 'Заголовок',
            'aboutEn' => 'Анонс',
            'textEn' => 'Полный текст',
			'metaTitleEn' => 'Мета-заголовок',
			'metaKeywordsEn' => 'Ключевые слова',
			'metaDescriptionEn' => 'Мета-описание',
        ];
    }
}
