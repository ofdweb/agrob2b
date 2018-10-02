<?php
/**
 * AccessController for Yii2
 *
 * @author Elle <elleuz@gmail.com>
 * @version 0.1
 * @package AccessController for Yii2
 *
 */
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

use DOMDocument;

use frontend\models\Elevators;
use frontend\models\ElevatorsRegions;
use frontend\models\ElevatorsCategory;
use frontend\models\ElevatorsItem;

class GrainController extends Controller
{
    public function behaviors()
    {
        return [
			'access' => [
				'class' => \backend\behaviors\AccessBehavior::className(),
			],
        ];
    }
    
    public function actionImport()
    {
        if (Yii::$app->request->isPost)
        {
            $file = UploadedFile::getInstanceByName('file');
            
            $xml = new DOMDocument();
            
            if (@$xml->load($file->tempName))
            {
                $root = $xml->documentElement;
                
                $elevators = [];
                foreach ($root->getElementsByTagName('elevator') as $el)
                {
                    $id = $el->getElementsByTagName('ID')->item(0)->nodeValue;
                    $elevators[$id] = [
                        'code' => $id,
                        'name' => $el->getElementsByTagName('Name')->item(0)->nodeValue,
                        'fName' => $el->getElementsByTagName('fName')->item(0)->nodeValue,
                        'address' => $el->getElementsByTagName('actualAddress')->item(0)->nodeValue,
                        'isMEZ' => $el->getElementsByTagName('isMEZ')->item(0)->nodeValue == 'Да' ? 1 : 0,
                        'own' => $el->getElementsByTagName('own')->item(0)->nodeValue == 'Да' ? 1 : 0,
                    ];

                }
                $this->createElevators($elevators);
                
                $category = [];
                foreach ($root->getElementsByTagName('product_type') as $el)
                {
                    $id = $el->getElementsByTagName('ID')->item(0)->nodeValue;
                    
                    preg_match_all('/[А-Я][^А-Я]*/u', $el->getElementsByTagName('Name')->item(0)->nodeValue, $name, PREG_SET_ORDER);
                    
                    $result = [];
                    array_walk_recursive($name, function($value, $key) use (&$result){
                        if($value)  $result[] = $value;
                    });
                    $name = $this->mb_ucfirst(mb_strtolower(implode(' ', $result)));
                    
                    $category[$id] = [
                        'code' => $id,
                        'name' => $name,
                        'parentId' => 0,
                        'mainParentId' => 0,
                        'isType' => 1
                    ];

                }
                $categoryList = $this->createCategory($category);
                
                
                $categoryItems = [];
                foreach ($root->getElementsByTagName('product_king') as $el)
                {
                    $id = $el->getElementsByTagName('ID')->item(0)->nodeValue;
                    $parentId = 0;
                    
                    $type = $el->getElementsByTagName('Name_product_type')->item(0)->nodeValue;
                    if($type && isset($categoryList[$type]))
                    {
                        $parentId = $categoryList[$type]->id;
                    }
                    
                    $categoryItems[$id] = [
                        'code' => $id,
                        'name' => $this->mb_ucfirst($el->getElementsByTagName('Name')->item(0)->nodeValue),
                        'parentId' => $parentId,
                        'mainParentId' => $parentId,
                        'isKind' => 1
                    ];
                }
                $categoryList = $this->createCategory($categoryItems);

                
                $category = [];
                foreach ($root->getElementsByTagName('product') as $el)
                {
                    $id = $el->getElementsByTagName('ID')->item(0)->nodeValue;
                    $code = $el->getElementsByTagName('ID_product_kind')->item(0)->nodeValue;
                    
                    if(isset($categoryList[$code]))
                    {
                        $category[$id] = [
                            'code' => $id,
                            'name' => $this->mb_ucfirst($el->getElementsByTagName('Name')->item(0)->nodeValue),
                            'parentId' => $categoryList[$code]->id,
                            'mainParentId' => $categoryItems[$code]['mainParentId'],
                            'vatRate' => (int)$el->getElementsByTagName('VAT_rate')->item(0)->nodeValue,
                        ];    
                    }
                }
                $this->createCategory($category, true);
                
                
                $items = [];                
                foreach ($root->getElementsByTagName('item') as $el)
                {
                    $items[] = [
                        'categoryId' => $el->getElementsByTagName('ID_product')->item(0)->nodeValue,
                        'elevatorId' => $el->getElementsByTagName('ID_elevator')->item(0)->nodeValue,
                        'price' => (float)str_replace(',', '.', $el->getElementsByTagName('price')->item(0)->nodeValue),
                        'actual' => 1,
                    ];    
                }    
                
                $this->createItems($items);    
                
            }
            
        }
        return $this->render('import');
    }
    
    private function mb_ucfirst($str, $enc = 'utf-8') { 
    		return mb_strtoupper(mb_substr($str, 0, 1, $enc), $enc).mb_substr($str, 1, mb_strlen($str, $enc), $enc); 
    }
    
    private function createItems($items = [])
    {
        if($items)
        {
            $categoryList = ArrayHelper::map(ElevatorsCategory::find()->select(['code', 'id'])->where(['isKind' => 0])->asArray()->All(), 'code', 'id');
    
            foreach(Elevators::find()->All() as $el)
            {
                $elevatorList[$el->code] = $el;
            }
            
            ElevatorsItem::deleteAll(['LIKE', 'dateAdd', date('Y-m-d')]);
            ElevatorsItem::updateAll(['actual' => 0]);
             
            foreach($items as $el)
            { 
                $model = new ElevatorsItem();
                $model->attributes = $el;
                $model->categoryId = $categoryList[$model->categoryId];
                $model->regionId = $elevatorList[$model->elevatorId]->regionId;
                $model->mainRegion = $elevatorList[$model->elevatorId]->mainRegion;
                $model->elevatorId = $elevatorList[$model->elevatorId]->id;

                if($model->validate())
                {
                    $model->save();
                }
                else{
                    vd($model->getErrors());
                }
            }
        }
                        
    }
    
    private function createCategory($items = [], $withoutKind = false)
    {
        $result = [];
        
        if($items)
        {
            $catList = ElevatorsCategory::find()->select(['code'])->asArray();
            if($withoutKind) $catList->where(['<>', 'isKind', 1]);
            
            $list = array_diff(
                ArrayHelper::getColumn($items, 'code'), 
                ArrayHelper::getColumn($catList->All(), 'code')
            );
            
            if($list)
            {
                foreach($list as $el)
                {
                    $model = new ElevatorsCategory();
                    $model->attributes = $items[$el];

                    if($model->validate())
                    {
                        $model->save();
                    }
                    else{
                        vd($model->getErrors());
                    }
                }
            }
        }
        
        foreach(ElevatorsCategory::find()->All() as $el)
        {
            $result[$el->isType ? $el->name : $el->code] = $el;
        }
        
        return $result;
    }
    
    private function createElevators($items = [])
    {
                if($items)
                {
                    $list = array_diff(
                        ArrayHelper::getColumn($items, 'code'), 
                        ArrayHelper::getColumn(Elevators::find()->select(['code'])->asArray()->All(), 'code')
                    );
                    
                    if($list)
                    {
                        foreach(ElevatorsRegions::find()->All() as $el)
                        {
                            $regionList[] = [
                                'id' => $el->id,
                                'name' => explode(' ', $el->name)[0],
                                'mainRegion' => $el->mainRegion,
                                'topId' => $el->topId
                            ];
                        }
                        
                        foreach($list as $el)
                        {
                            $model = new Elevators();
                            $model->attributes = $items[$el];
                            
                            foreach($regionList as $reg)
                            {
                                if( mb_strstr($items[$el]['address'], $reg['name']) )
                                {
                                    $model->regionId = $reg['id']; 
                                    $model->mainRegion = $reg['mainRegion']; 
                                    $model->topId = $reg['topId']; 
                                    
                                    break;
                                }    
                            }
                            
                            if($model->validate())
                            {
                                $model->save();
                            }
                            else{
                                vd($model->getErrors());
                            }
                        }
                    }
                }
    }
}