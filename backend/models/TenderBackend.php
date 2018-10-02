<?php
namespace backend\models;

use common\models\Tenders;
use common\models\TenderLots;
use Yii;

use yii\helpers\Html;
/**
 * Signup form
 */
class TenderBackend extends Tenders
{
    public $unActive;
    public $win;
    
    public function getLotsTender()
	{
		return $this->hasMany(TenderLots::className(), ['tenderId' => 'id'])->from(['lotList' => TenderLots::tableName()])->with('journal', 'winnerLot', 'company');
	}
    
    public function oneById($id=null){
        return Tenders::find()->where(['id'=>$id])->with('okpdsFull','addressesFull','staff','company')->one();
    }
    
    public function lotsList($id=null){
        return Tenders::find()->where(['parentId'=>$id])->with('okpdsFull','addressesFull','staff','company')->all();
    }
    
    public function itemListProvider(){
        $model = TenderBackend::find()->orderBy(['id' => SORT_DESC])->with('staff','company')->where(['parentId'=>0]);
        if(Yii::$app->request->get('option_5'))
        {
            $model->joinWith('lotsTender')->andwhere(['<>', 'lotList.winId', 0]);
        }
        else
        {
            $model->with('lotsTender');
        }
        
        return $model;
    }
    
    public function formatingOkpd($items=null){
        $result=[];
        $i=1;
        if($items){
            foreach($items as $el){
                $result[]=$i.') '.$el->okpdItem->name;
                $i++;
            }
        }
        return implode('<br/>',$result);
    }
    
    public function formatingAddress($items=null){
        $result=[];
        $i=1;
        if($items){
            foreach($items as $el){
                $result[]=$i.') '.$el->address->address;
                $i++;
            }
        }
        return implode('<br/>',$result);
    }
    
    public function formatingCompany($items=null){
        $result=[];
        if($items){
            foreach($items as $el){
                $result[]=Html::a($el->company->name,['/company/view','id'=>$el->company->id]);
            }
        }
        return implode('<br/>',$result);
    }
    
    public function formatingEmails($items=null){
        $result=[];
        if($items){
            foreach($items as $el){
                $result[]=$el->email;
            }
        }
        return implode('<br/>',$result);
    }
}
