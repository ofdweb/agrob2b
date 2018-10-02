<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

use common\models\Items;
use common\models\Currency;
use common\models\CurrencyHistory;
use common\models\Themes;
use common\models\CompanyStatisticsFilterForm;
use common\models\Company;
use common\models\User;
use common\models\UserHistoryLog;
use common\models\TarifList;
use common\models\TarifListJournal;
use common\models\Billing;
use common\models\Delivery;
use common\models\Tenders;
use common\models\TendersHistory;

use common\components\THelper;

use common\components\Delivery as Newdelivery;

/**
 * Site controller
 */
class StatisticsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
			'access' => [
				'class' => \backend\behaviors\AccessBehavior::className(),
			],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }
	
	public function actionCompany()
	{	
		$filterForm = new CompanyStatisticsFilterForm();
		$filterForm->setDefaultDate();
		
		if (Yii::$app->request->get('CompanyStatisticsFilterForm')) {			
			$filterForm->load(Yii::$app->request->get());
			$filterForm->initDate();
		}

		return $this->render('index', [
			'title' => 'Общая статистика по компаниям',
			'model' => $filterForm,
            'stats' => Company::getStatistics($filterForm),
        ]);
	}
	
	public function actionAllstatistic() {
		
		$cache = Yii::$app->cache;
        $userCount = $cache->get('userCount');
        
        if ($userCount === false) {
        	$userCount = User::find()->count();
        	$cache->set('userCount', $userCount, 86400);
        }
        
    	$tenderModel = $cache->get('tenderModel');
        
        if ($tenderModel === false) {
        	$tenderModel = Tenders::find()->select('dateCreate')->where(['parentId' => 0]);
        	$cache->set('tenderModel', $tenderModel, 86400);
        } 
        
        $tenderCount = $cache->get('tenderCount');
        
        if ($tenderCount === false) {
        	$tenderCount = $tenderModel->count();
        	$cache->set('tenderCount', $tenderCount, 86400);
        }

        $date = date("Y-m-d H:i:s", strtotime('-60 minutes'));
        
        $model = $cache->get('model');
        
        if ($model === false) {
        	$model = UserHistoryLog::find()->select('userId')->where(['>', 'dateAdd', $date])->all();
        	$cache->set('model', $model, 86400);
        }
        
        $data = [];
        foreach ($model as $el) $data[$el->userId] = $el->userId;
        //$online_1=count($data); 

        $model = TendersHistory::find()->select('userId')->where(['>', 'dateAction', $date])->all();
        foreach ($model as $el) $data[$el->userId] = $el->userId;
        //$online_2=count($data);

        //$startDate = date('Y-m-d',strtotime('-1 month'));
        //$stopDate = date('Y-m-d');
        
        $modelTarifList = $cache->get('modelTarifList');
        
        if ($modelTarifList === false) {
        	$modelTarifList = TarifList::billingStatistic();
        	$cache->set('modelTarifList', $modelTarifList, 86400);
        }
       
        /*
                $rangePrice=[];
                foreach($modelTarifList[0]['range'] as $el)   $rangePrice[$el->id]=$el->price;
                foreach ($modelTarifList as $el){
                    $rangeId=TarifList::rangeOne($el)->currentRangeId;
                    $balance=Billing::calculateBalance($el->balance);
                    $total=$el->countVictory*$rangePrice[$rangeId];
                    $dolg=($total-$balance)>0?$total-$balance:0;

                    $allTotal+=$total;
                    $allTotalDolg+=$dolg;
                }
         */
        
        $countWin = $cache->get('countWin');
        
        if ($countWin === false) {
        	$countWin = TenderLots::find()->select('id')->where(['!=', 'winId', 0])->count();
        	$cache->set('countWin', $countWin, 86400);
        }
        
        $allTotal = $cache->get('allTotal');
        $allTotalDolg = $cache->get('allTotalDolg');
        
        if ($allTotal === false || $allTotalDolg === false) {
        	
        	foreach ($modelTarifList as $el) {
        		if (!$el->company->billingBlackList) {
        			$actSumm = 0;
        			$balance = 0;
        	
        			if ($el->billingActs) {
        				foreach ($el->billingActs as $act) {
        					if ($act->status == 1) {
        						if ($act->type != 0) $actSumm += $act->price;
        						else   $balance += $act->price;
        					}
        				}
        			}
        	
        			$journal = TarifListJournal::formatingVictory($el->journal);
        	
        			$total = $journal['total'] ? $journal['total'] : 0;
        			$dolg = ($total + $actSumm - $balance) > 0 ? $total + $actSumm - $balance : 0;
        	
        			$allTotal += $balance;
        			$allTotalDolg += $dolg;
        		}
        	}
        	
        	$cache->set('allTotal', $allTotal, 86400);
        	$cache->set('allTotalDolg', $allTotalDolg, 86400);
        }

        $tenderList = [];
        $tenderModel = $tenderModel->andWhere(['>', 'dateCreate', date('Y-m-d', strtotime('-3 month'))])->All();
        foreach ($tenderModel as $el) {
            $key = explode(' ', $el->dateCreate);
            // $key=strtotime('2015-10-01');
            $key = strtotime($key[0]);
            $tenderList[$key] += 1;
            // break;
        }
        //var_dump($tenderList);die;
        
        $countWin = $cache->get('countWin');
        
        if ($countWin === false) {
        	$countWin = TenderLots::find()->select('id')->where(['!=', 'winId', 0])->count();
        	$cache->set('countWin', $countWin, 86400);
        }
        
        $winPriceModel = $cache->get('winPriceModel');
        
        if ($winPriceModel === false) {
        	$winPriceModel = TenderLots::find()->select('bestPrice')->where(['!=', 'winId', 0])->all();
        	$cache->set('winPriceModel', $winPriceModel, 86400);
        }
        
        $winPrice = 0;
        foreach ($winPriceModel as $el) $winPrice += $el->bestPrice;

        $newUsersCount = $cache->get('newUsersCount');
        
        if ($newUsersCount === false) {
        	$newUsersCount = User::find()->where(['>=', 'dateCreate', date('Y-m-d', strtotime('- 7 day'))])->count();
        	$cache->set('newUsersCount', $newUsersCount, 86400);
        }
        
        $newCompanyWeekCount = $cache->get('newCompanyWeekCount');
        
        if ($newCompanyWeekCount === false) {
        	$newCompanyWeekCount = Company::find()->where(['>=', 'dateCreate', date('Y-m-d', strtotime('- 7 day'))])->count();
        	$cache->set('newCompanyWeekCount', $newCompanyWeekCount, 86400);
        }
        
        $newCompanyMonthCount = $cache->get('newCompanyMonthCount');
        
        if ($newCompanyMonthCount === false) {
        	$newCompanyMonthCount = Company::find()->where(['>=', 'dateCreate', date('Y-m-d', strtotime('- 1 month'))])->count();
        	$cache->set('newCompanyMonthCount', $newCompanyMonthCount, 86400);
        }

        return $this->render('allstatistic', [
            'userCount' => $userCount,
            'tenderCount' => $tenderCount,
            'online' => count($data),
            'allTotal' => number_format($allTotal, 0, '.', ' ') . ' руб.',
            'allTotalDolg' => number_format($allTotalDolg, 0, '.', ' ') . ' руб.',
            'tenderList' => $tenderList,
            'countWin' => $countWin,
            'winPrice' => number_format($winPrice, 0, '.', ' ') . ' руб.',
            'newUsersCount' => $newUsersCount,
            'newCompanyWeekCount' => $newCompanyWeekCount,
            'newCompanyMonthCount' => $newCompanyMonthCount
        ]);
	}
	
	public function actionCompany2()
	{	
		$filterForm = new CompanyStatisticsFilterForm();
		$filterForm->setDefaultDate();
		
		if (Yii::$app->request->get('CompanyStatisticsFilterForm')) {			
			$filterForm->load(Yii::$app->request->get());
			$filterForm->initDate();
		}

		return $this->render('company2', [
			'title' => 'Статистика побед по компаниям',
			'model' => $filterForm,
            'companies' => Company::getStatistics2($filterForm),
        ]);
	}
    
    public function actionBilling()
	{	
        $filterForm = new CompanyStatisticsFilterForm();
        $filterForm->isOnlyActiveCompany=1;
		
		$filterForm->startDate = date('Y-m-01');
		$filterForm->stopDate = date('Y-m-t');
		
		if (Yii::$app->request->get('CompanyStatisticsFilterForm')) {			
			$filterForm->load(Yii::$app->request->get());
			$filterForm->initDate();
			$filterForm->startDate=date('Y-m-01',$filterForm->startDate);
			$filterForm->stopDate=date('Y-m-t',$filterForm->stopDate);

		}

        
        
        $model=TarifList::billingStatistic($filterForm->startDate,$filterForm->stopDate);
			
			//var_dump(count($model));die;
        if($filterForm->isOnlyActiveCompany){
            //foreach($model as $key=>$el){
                //if(!$el->countVictory)  unset($model[$key]);
            //}
        }

        return $this->render('billing', [
			'title' => 'Обороты/долги по компаниям c '.$filterForm->startDate.' по '.$filterForm->stopDate,
			'model' => $filterForm,
            'companies'=>$model,
            'blackList'=>$modelBlack,
            'isOnlyActiveCompany'=>$filterForm->isOnlyActiveCompany
        ]);
	}
    
    
    public function actionBillingCompanyLoad()
    {
        $params = Yii::$app->request->get();
		
        Yii::$app->response->getHeaders()->set('X-PJAX-Url', Yii::$app->request->referrer);
        
        $itemList = TarifListJournal::billingCompanyStatistic([$params['companyId']], $params['startDate'], $params['stopDate']);
        
        $list=[];
        if($itemList)
        {
            foreach($itemList as $key => $el)
            {
                $i = date('m-Y', strtotime($el->dateAdd));
                if($i == $params['month'])
                {
                    $list[$el->company->getCompanyName()] ++;
                }
            }
        }
        
        return $this->renderAjax('billingCompanyAjax', [
			'list' => $list
        ]);
    }
    
    public function actionBillingCompany()
	{	
        $filterForm = new CompanyStatisticsFilterForm();
		$itemList=[];
        
		if (Yii::$app->request->get('CompanyStatisticsFilterForm')) {			
			$filterForm->load(Yii::$app->request->get());
			$filterForm->initDate();
			$filterForm->startDate=date('Y-m-d',$filterForm->startDate);
			$filterForm->stopDate=date('Y-m-d',$filterForm->stopDate);
            
            $itemList = TarifListJournal::billingCompanyStatistic($filterForm->companyId, $filterForm->startDate, $filterForm->stopDate);
			
			$t = TarifListJournal::formatingVictory($itemList);
			
            $list=[];
            if($itemList){
                //$prev_i = date('Y-m',strtotime(current($itemList)->dateAdd));
                foreach($itemList as $key => $el){
                    $i = date('m-Y',strtotime($el->dateAdd));
                    $company = $el->lot->companyId;
                   /* $i_next = date('Y-m',strtotime($itemList[$key + 1]->dateAdd));
                    $company = $el->lot->companyId;
                    
                    if(!isset($list[$company][$i]['start']) || !$list[$company][$i]['start'])     $list[$company][$i]['start']=date('Y-m-d',strtotime($el->dateAdd));
                    if($i != $i_next)    $list[$company][$i]['end']=date('Y-m-d',strtotime($el->dateAdd));
                    
                    $list[$company][$i]['items'][] = $el;
                    */
					$list[$company][$i][]=$el;
					$mounth[$i] = $i;
                    //$prev_i = $i;
                }   
                
                //if(!isset($list[$company][$i]['end']) || !$list[$company][$i]['end'])       $list[$company][$i]['end'] = date('Y-m',strtotime(last($itemList)->dateAdd));
            }
                        
		}

        $companyList = THelper::cmapCompany(Company::find()->where(['forDelete'=>0])->All(), 'id', ['nameShort','id','active'],' ; ');
        return $this->render('billingCompany', [
			'title' => 'Прибыль по компании c '.$filterForm->startDate.' по '.$filterForm->stopDate,
			'model' => $filterForm,
            'itemList'=>$list,
            'companyList'=>$companyList,
            'mounth' => $mounth,
			't' => $t,
        ]);
	}
    
    public function actionActslist(){
        $filterForm = new CompanyStatisticsFilterForm();
        //$filterForm->setDefaultDate();
        
        if (Yii::$app->request->get('CompanyStatisticsFilterForm')) {			
			$filterForm->load(Yii::$app->request->get());
			$filterForm->initDate();
		}
        
        if($post=Yii::$app->request->post()){
            $date['start']=$filterForm->startDate;
            $date['stop']=$filterForm->stopDate;

            $pathToFile=[];
            
            if($post['emailType']==1){
                $pathToFile[]=Billing::docsGenerate('acts','F',$date);
                $pathToFile[]=Billing::docsGenerate('onpay','F',$date);
                $pathToFile[]=Billing::docsGenerate('billing','F',$date);    
            }
            else if($post['emailType']==2){
                $pathToFile[]=Billing::docsGenerateExcel('acts','F',$date);
                $pathToFile[]=Billing::docsGenerateExcel('onpay','F',$date);
                $pathToFile[]=Billing::docsGenerateExcel('billing','F',$date);    
            }
            
            if($pathToFile){
                $emailTo=$post['emailUser'];
                
                $model=Billing::oneById($id);
                $title=Yii::t('c', 'Документы за период ').($filterForm->startDate?('с '.date('Y-m-d',$filterForm->startDate).' по '.date('Y-m-d',$filterForm->stopDate)):'');
                $from=Yii::$app->params['adminEmail'];

				/*
                if($emailTo && Delivery::createDelivery([
     			    'uidFrom' =>Yii::$app->params['adminId'],
         			'uids' => 0,
                    'emailTo'=>$emailTo,
                    'attach'=>implode(';',$pathToFile),
         			'subject' => $title,
         			    'text' => Yii::$app->controller->renderPartial('@common/mail/docs_acts_and_onpay',['date'=>$date])
	           ]))     Yii::$app->session->setFlash('success', 'Данные успешно отправлены');    
               else     Yii::$app->session->setFlash('danger', 'Ошибка отправки данных'); 
			   */
			   
			   
				if($emailTo) {
					$emailsTo = explode(',', $emailTo);
					$emailsTo = array_unique($emailsTo);
				}
				
				$attachments = [];
				foreach($pathToFile as $pathToSingleFile) {
					$fileName = basename($pathToSingleFile).PHP_EOL;
					$attachments[$fileName] = base64_encode(file_get_contents($pathToSingleFile));
				}
				
				$result = Newdelivery::addMessageToDelivery(
				[
					'subject' => $title,
					'body' => Yii::$app->controller->renderPartial('@common/mail/docs_acts_and_onpay',['date'=>$date]), 
					'emailTo' =>  $emailsTo,
					'emailFrom' => User::find()->where(['id' => Yii::$app->params['adminId']])->one()->userName,
					'attachments' => $attachments,
					'projectId' => 2,
				]);
				
				if($emailTo && $result) {
					Yii::$app->session->setFlash('success', 'Данные успешно отправлены');
				}
				else {
					Yii::$app->session->setFlash('danger', 'Ошибка отправки данных'); 
				}
				
            }
            else    Yii::$app->session->setFlash('danger', 'Ошибка формирования данных'); 
        }
        
        return $this->render('actslist', [
			'title' => 'Счета, акты, оплаты '.($filterForm->startDate?('с '.date('Y-m-d',$filterForm->startDate).' по '.date('Y-m-d',$filterForm->stopDate)):''),
			'model' => $filterForm,
            'companies'=>$model
        ]);
        
    }
    
    public function actionActslistview($type=null,$start=null,$stop=null){
        if($type){
            $date['start']=$start;
            $date['stop']=$stop;
            Billing::docsGenerate($type,'I',$date);
        }
    }
    
    public function actionActslistdownload($type=null,$start=null,$stop=null){
        if($type){
            $date['start']=$start;
            $date['stop']=$stop;
            Billing::docsGenerateExcel($type,'I',$date);
        }
    }

}