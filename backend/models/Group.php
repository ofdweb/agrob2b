<?php

namespace backend\models;

use Yii;
use common\behaviors\ActionLogsBehavior;
use common\models\Okpd;
use common\models\Tenders;
use common\models\Files;
use common\models\Address;
use common\models\CompanyDivision;
use common\models\TenderGroupCache;
use yii\helpers\ArrayHelper;

use frontend\models\DefaultWidgetsByGroup;

/**
 * This is the model class for table "b2b_Group".
 *
 * @property integer $id
 * @property string $title
 * @property string $buttonSell
 * @property string $buttonBuy
 * @property string $dateAdd
 * @property integer $active
 */
class Group extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_Group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['dateAdd'], 'safe'],
            [['active','imageId','visibleMenu','visibleOkpd','visibleSearch','visibleConstructor','visibleWidgets','visibleReport'], 'integer'],
            [['title', 'buttonSell', 'buttonBuy'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Название',
            'buttonSell' => 'Текст кнопки Продать',
            'buttonBuy' => 'Текст кнопки Купить',
            'dateAdd' => 'Добавлено',
            'active' => 'Активная',
            'imageId'=>'Изображение',
            'file'=>'Изображение',
            'visibleOkpd'=>'Отображение в списке выбора при ОКПД', 
            'visibleSearch'=>'Отображение в поиске',  
            'visibleConstructor'=>'Отображение в конструкторе заявки',  
            'visibleWidgets'=>'Просмотр доп. опций в карточке заявки',  
            'visibleReport'=>'Просмотр отчетов',            
            'visibleMenu'=>'Отображение в шапке',            
        ];
    }
    
    public function behaviors()
    {
        return [
            'ActionLogsBehavior' => [
                'class' => ActionLogsBehavior::className(),
            ],
        ];
    }
    
    public $file;    
    public $divisionTree=[];
    public $divisionTreeTmp=[];
    public $categorySearch=[];
    public $categorySelected=[];
    
    public function beforeDelete()
    {
        $this->deleteRelations($this->id);
        if($this->imageId)  $this->deleteImage($this->imageId);
        return parent::beforeDelete();
    }
    
    private function deleteRelations($groupId=null){
        GroupCompanyRelations::deleteAll(['groupId' => $groupId]);
        GroupOkpdRelations::deleteAll(['groupId' => $groupId]);
    }
    
    private function deleteImage($imageId=null) {
        if ($model = Files::oneById($imageId)) {
            $model->delete();
        }
    }
    
    public function getOkpdRelation(){
        return $this->hasMany(GroupOkpdRelations::className(), ['groupId' => 'id'])->with('okpdGraph','okpd');
    }  
    
    public function getCompanyRelation(){
        return $this->hasMany(GroupCompanyRelations::className(), ['groupId' => 'id']);
    }  
    
    public function getImage(){
        return $this->hasOne(Files::className(), ['id' => 'imageId']);
    } 
    
	public function getDefaultwidgetsgroups() {
		return $this->hasMany(DefaultWidgetsByGroup::className(), ['idGroup' => 'id']);
	}
	
    public function itemList() {
        return Group::find()->where(['active'=>1])->with(['okpdRelation','image','companyRelation'])->All();
    }
	
    public function itemListIds(){
        return Group::find()->select(['id','title'])->where(['active'=>1])->with(['okpdRelation'])->orderBy(['dateAdd'=>SORT_DESC])->asArray()->All();
    }
    
    public function isContainCompany($companyId=null){
        $companyId=$companyId?$companyId:Yii::$app->user->identity->company->id;
        if($this->companyRelation && $companyId){
            foreach($this->companyRelation as $el){
                if($el->companyId==$companyId) return true;
            }
        }
        return false;
    }
        
    public function formattingTreeDivision($search=null){
        $result=[];
        if($this['divisionTree']){
            if($search){
                $this->categorySearch = ArrayHelper::getColumn(Okpd::find()->select(['id'])->where(['like','name',$search])->all(),'id'); 
            }
            foreach($this['divisionTree'] as $group){
                if($item=self::treeDivisionFormatting($group['okpdRelation'])){
                    $result[]=[
                        'groupName'=>$group['title'],
                        'items'=>$item
                    ];    
                }
            }
        }
        return $result;
    }
    
    private function treeDivisionFormatting($okpdRelation=[]){
        $items=[];
        if($okpdRelation){
            foreach($okpdRelation as $okpd){
                $this->divisionTreeTmp=[];
                
                if($okpd['okpdGraph']){
                    foreach($okpd['okpdGraph'] as $el){
                        if($el['okpdChild']['parentId'])    $this->divisionTreeTmp[$el['okpdChild']['parentId']][]=$el;
                    }
                }
                
                $children=self::treeDivision($okpd['okpd']['id']);
                if(!$this->categorySearch || ($this->categorySearch && (in_array($okpd['okpd']['id'],$this->categorySearch) || $children))){
                    $items[]=[
                        'title' => $okpd['okpd']['name'],
                        'id'=>$okpd['okpd']['id'],
                        'children' => $children,
                        'level'=>$okpd['okpd']['level'],
                        'checked'=>isset($this->categorySelected[$okpd['okpd']['id']])?'checked':'',
                        'regions'=>$this->categorySelected[$okpd['okpd']['id']]['regions'],
                    ];    
                }
                
            }
               
        }
        return $items;
    }
    
    private function treeDivision($parentId=0){
        $items =[];
        
        if($this->divisionTreeTmp[$parentId]){
            foreach ($this->divisionTreeTmp[$parentId] as $term) {
                  $children = self::treeDivision($term['okpdChild']['id']);

                  if(!$this->categorySearch || ($this->categorySearch && (in_array($term['childId'],$this->categorySearch) || $children))){
                      $items[] = [
                        'title' => $term['okpdChild']['name'],
                        'id'=>$term['childId'],
                        'children' => $children,
                        'level'=>$term['okpdChild']['level'],
                        'checked'=>isset($this->categorySelected[$term['childId']])?'checked':'',
                        'regions'=>$this->categorySelected[$term['childId']]['regions'],
                      ];  
                  }
            }    
        }
        return $items;
    }
    
    public function closedPricesTenders(){
        $result=[];
        if($tenderList=Tenders::searchClosedPrices()){ 
            $cityTire=ArrayHelper::getColumn(Address::find()->select(['name'])->where(['LIKE','name','-'])->andWhere(['socr'=>['г','Респ','р-н','пгт','обл']])->asArray()->all(),'name');
            $citySpace=ArrayHelper::getColumn(Address::find()->select(['name'])->where(['LIKE','name',' '])->andWhere(['socr'=>['г','Респ','р-н','пгт','обл']])->asArray()->all(),'name');

            foreach($tenderList as $el){
                $okpds=$el['okpds']?ArrayHelper::getColumn($el['okpds'],'okpdId'):[];
                $result[$el['companyId']][$el['id']]=[
                    'rates'=>$el['ratesFull'],
                    'city'=>self::semanticDescr($el['productDesc'],$cityTire,$citySpace),
                    'okpds'=>$okpds,
                ];
            }
        }
        
        
        if($result){
            $groupdOkpdList=GroupOkpdRelations::arrayListWithChilds(); 

            foreach($result as $compId=>$tender){
                foreach($tender as $tenderId=>$el){
                    $okpdCache=[];
                    $cityCache=[];
                        
                    if($el['city'] && $el['okpds']){
                        $rateCompanyList=[];
                        
                        if($el['rates']){
                            foreach($el['rates'] as $e)    $rateCompanyList[]=$e['user']['companyId'];
                        }
                        
                        foreach($el['okpds'] as $divId){
                            $isGroup=isset($groupdOkpdList[$divId])?1:0;
                            
                            CompanyDivision::addItem($compId,$divId,2,$isGroup);
                            $okpdCache[]=$divId;
                            
                            if($rateCompanyList){
                                foreach($rateCompanyList as $cid){
                                    CompanyDivision::addItem($cid,$divId,1,$isGroup);
                                    
                                }  
                            }
                                
                            foreach($el['city'] as $city){
                                GroupRegionRelations::addItem($compId,$divId,2,$city['id']);
                                $cityCache[]=$city['id'];
                                
                                if($rateCompanyList){
                                    foreach($rateCompanyList as $cid)     GroupRegionRelations::addItem($cid,$divId,1,$city['id']);
                                }
                            }
                        }

                        $rateCompanyList[]=$compId;
                        foreach($rateCompanyList as $cid){
                            $selectedGroup = ArrayHelper::getColumn(CompanyDivision::itemList($cid,[1,2],1),'divisionId');
                            $groups=GroupOkpdRelations::groupsByDivision($selectedGroup);
                            if($groups) GroupCompanyRelations::rewriteGroups($groups,$cid);     
                        }
                    }
                    
                    TenderGroupCache::add($compId,$tenderId,$okpdCache,$cityCache); 
                    
                }
            }
            var_dump('Update '.count($tenderList).' tender(s)');
        }
        else{
            var_dump('Tenders missing');
        }    

        return $result;
    }
    
    
    public function semanticDescr($string=null,$cityTire,$citySpace){
        $result=[];

        $string=trim($string);
        $string = strip_tags($string);
        //$string = strtolower($string);
        
        if($string){
            $nidl=['Лбинск','Спб ','спб ','СПБ ','Н.'];
            $res=['Лабинск','Санкт-Петербург ','Санкт-Петербург ','Санкт-Петербург ','Нижний '];                                    
            $string=str_replace($nidl,$res,$string);
            
            $string=str_replace([',','.','+'],' ',$string);
            $string = preg_replace('/\(.*?\)/', '', $string); 
            $string = preg_replace('/\".*?\"/', '', $string); 
            $string=str_replace(['"','/',';',':','(',')'],'',$string);
            $string=preg_replace("/[0-9]/","", $string);

//var_dump($string);die;
            $strList=self::checkList($string,$cityTire,$citySpace);
// var_dump($strList);die;         
            if($strList){                
                foreach($strList as $key => $el){
                    $res=self::searchWord($el);
                    if($res)    $result[]=$res;
                }
            }
        }
        
        return $result;
    }
    
    private function checkList($string=null,$cityTire,$citySpace){
        $strList=[];
        
        $stopWords = [
           'что', 'как', 'все', 'она', 'так', 'его', 'только', 'мне', 'было', 'вот',
           'меня', 'еще', 'нет', 'ему', 'теперь', 'когда', 'даже', 'вдруг', 'если',
           'уже', 'или', 'быть', 'был', 'него', 'вас', 'нибудь', 'опять', 'вам', 'ведь',
           'там', 'потом', 'себя', 'может', 'они', 'тут', 'где', 'есть', 'надо', 'ней',
           'для', 'тебя', 'чем', 'была', 'сам', 'чтоб', 'без', 'будто', 'чего', 'раз',
           'тоже', 'себе', 'под', 'будет', 'тогда', 'кто', 'этот', 'того', 'потому',
           'этого', 'какой', 'ним', 'этом', 'один', 'почти', 'мой', 'тем', 'чтобы',
           'нее', 'были', 'куда', 'зачем', 'всех', 'можно', 'при', 'два', 'другой',
           'хоть', 'после', 'над', 'больше', 'тот', 'через', 'эти', 'нас', 'про', 'них',
           'какая', 'много', 'разве', 'три', 'эту', 'моя', 'свою', 'этой', 'перед',
           'чуть', 'том', 'такой', 'более', 'всю','по','лот','морская','овощи','пшено',
           'новое','ЗАО','OOO','ИП','ОАО'
        ];

        if($string){
            foreach($cityTire as $el){
                if(mb_stristr($string,$el)){
                    $newEl=str_replace('-','==',$el);
                    $string=str_replace($el,$newEl,$string);
                }
            }
            foreach($citySpace as $el){
                if(mb_stristr($string,$el)){
                    $newEl=str_replace(' ','++',$el);
                    $string=str_replace($el,$newEl,$string);
                }
            }
            $string=str_replace('-',' ',$string);
            $string=str_replace('==','-',$string);
            
            $strList=explode(' ',$string);
            
            if($strList){
                foreach($strList as $key=>$el){
                    if(in_array($el,$stopWords) || mb_strlen($el)<3)    unset($strList[$key]);
                    else    $strList[$key]=str_replace('++',' ',$el);
                }
            }
        }
        
        return $strList;
    }
    
    private function searchWord($word=null){
        $result=[];
        
        if($word){
            $strLen=mb_strlen($word);
            
            $model=Address::find()->select(['id','name'])->where(['LIKE','name',$word.'%',false])->andWhere(['socr'=>['г','Респ','р-н','пгт','обл']])->asArray()->all();

                if($model){
                    $l=10000;
                    $elem=[];
                    foreach($model as $el){
                        $elLen=mb_strlen($el['name']);
                        if(($elLen-$strLen)<=$l){
                            $l=$elLen-$strLen;
                            if(abs($l)<=2)  $elem=$el;
                        }
                    }
                    
                    if($elem){
                        $result=[
                            'name'=>$elem['name'],
                            'id'=>$elem['id']
                        ];    
                    }
                }
        }
        
        return $result;
    }
}