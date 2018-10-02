<?php

namespace backend\behaviors;

use yii\db\BaseActiveRecord;
use yii\db\Expression;
use yii\base\Behavior;	
use backend\modules\Pages\models\Page;

class UrlPageSaveBehavior extends Behavior {
	
	public $strFile = '/../../frontend/config/url-pages.json';
	public $strFieldName = 'Url';
    
	public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'save',
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE => 'save'			
        ];
    }
	
    public function save($attribute)
    {
		$aPages = Page::find()->select('url')->asArray()->All();
		
		$aUrls = [];		
		foreach ($aPages as $aPage) {
			$aUrls[] = $aPage['url'];
		}

		file_put_contents(__DIR__ . $this->strFile, json_encode($aUrls));
    }
}

