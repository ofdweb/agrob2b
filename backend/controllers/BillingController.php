<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use common\components\THelper;

use yii\helpers\Url;
use common\models\ActionLogs;
use common\models\QueueFiles;
use common\models\Billing;
use common\models\Company;
use common\models\User;
use common\models\TarifList;
use common\models\BillingDepositActs;
use common\models\BillingReceipt;
use common\models\Delivery;
use common\models\BillingReconciliations;

use backend\models\TarifCompanyBlack;

use common\models\UploadedFileCustom;
use common\components\QHelper;

use common\components\Delivery as Newdelivery;

/**
 * Site controller
 */
class BillingController extends Controller
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
    
    public function actionIndex(){
        $model=new Company();
        $query=Company::find()->orderBy(["id" => SORT_DESC ])->where(['forDelete'=>0]);

        $badInn=ArrayHelper::map(Company::compareINN(), 'id', 'id');

        $directors=ArrayHelper::map(User::find()->where(['isDirector'=>1])->All(), 'id', 'name');

        if(isset($_GET['Company'])){
            $params=$_GET['Company'];
            $params['addRate']=$_GET['addRate'];
            $params['addRequist']=$_GET['addRequist'];
            $params['active']=$_GET['active'];
            
            if($params['director']){
                $uid=ArrayHelper::map(User::find()->andWhere(['like', 'name', $params['director']])->orWhere(['like', 'lastName', $params['director']])->orWhere(['like', 'patronymic', $params['director']])->All(), 'id', 'id');
                $query->andWhere(['director'=>$uid]);
                $model->director=$params['director'];
            }
            unset($params['director']);
            foreach($params as $key=>$el){
                if($el){
                    $query->andWhere(['like', $key, $el]);
                    $model->$key=$el;    
                }
            }
        }
        
		$dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'directors'=>$directors,
            'model' => $model,
            'badInn'=>$badInn
        ]);
    }
	
    public function actionRebuildAct()
    {
        $model = new \yii\base\DynamicModel(['id']);
        $model->addRule(['id'], 'safe');
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $result = Billing::rebuildAct($model->id);
            
            if ($result) {
                if ($result->hasErrors()) {
                    var_dump($result->getErrors());die;
                }
            }
        }
        
        return $this->render('rebuild_act', compact('model'));
        
    }
    
	public function actionList($id=0,$tarifId=null){
        if($id){
            $balance=Billing::balanceByCompanyId($id);
            $modelCompany=Company::companyById($id);
            $confirmList=Billing::confirmByCompanyIdProvider($id);
            
            if($tarifId)    $confirmList=$confirmList->andWhere(['tarifId'=>$tarifId]);    
        }

        $model=new Billing();
         
        if(isset($_GET['Billing'])){
            $params=$_GET['Billing'];
            
            if($params['companyId']){
                $uid=ArrayHelper::map(Company::find()->where(['forDelete'=>0])->andWhere(['like', 'nameShort', $params['companyId']])->All(), 'id', 'id');
                $confirmList->andWhere(['companyId'=>$uid]);
                $model->companyId=$params['companyId'];
            }
            unset($params['companyId']);
            foreach($params as $key=>$el){
                if(($el!='all')){
                    $confirmList->andWhere(['like', $key, $el]);
                    $model->$key=$el;    
                } 
            }
        }
        
        
        $dataProvider = new ActiveDataProvider([
            'query' =>$confirmList,
        ]);
        
        return $this->render('list', [
            'balance' => $balance,
            'model'=>$model,
            'modelCompany'=>$modelCompany,
            'dataProvider'=>$dataProvider,
        ]);
	}
	
	public function actionActs($id=null) {
		$company = Company::findOne($id);
		
		if (Yii::$app->request->post('date')) {
			$billingReconciliation = new BillingReconciliations();
			$billingReconciliation->date = Yii::$app->request->post('date');
			$billingReconciliation->companyId = $id; 
			$billingReconciliation->companyName = $company->name; 
			
			$billingReconciliation->directorId = (isset($company->getDirector()->id)) ? $company->getDirector()->id : current($company->users)->id;
			
			$director = User::findOne($billingReconciliation->directorId);			
			$billingReconciliation->directorName = $director->getFullName();
			
			$billingReconciliation->calculate();
		}
		
		return $this->render('acts_reconciliation', [
			'list' => BillingReconciliations::find()->where(['companyId' => $id ])->orderBy("dateCreate DESC")->all(),
			'company' => $company
		]);
	}
	
	public function actionDownloadActsExcel() {
		if (isset($_GET['id']) && ($id = intval($_GET['id']))) {
			//$billingReconciliation = BillingReconciliations::findOne($id);
            
            QHelper::buildDocument([
                    'targetId' => $id,
                    'class' => 'common\models\BillingReconciliations',
                    'method' => 'generateDocument',
                    'description' => Yii::t('controller', 'Акт сверки № АСЭТП-{id} ({type})', ['id' => $id, 'type' => 'excel']),
                    'params' => [
                        'id' => $id,
                        'type' => 'excel'
                    ]
            ]);
            
            return $this->redirect(Yii::$app->request->referrer);
		
			//if ($billingReconciliation->id) return $billingReconciliation->generateExcel();
		} else return $this->redirect('/personal/billing/acts');
	}
	
	public function actionDownloadActsPdf() {
		if (isset($_GET['id']) && ($id = intval($_GET['id']))) {
			//$billingReconciliation = BillingReconciliations::findOne($id);
            
            QHelper::buildDocument([
                    'targetId' => $id,
                    'class' => 'common\models\BillingReconciliations',
                    'method' => 'generateDocument',
                    'description' => Yii::t('controller', 'Акт сверки № АСЭТП-{id} ({type})', ['id' => $id, 'type' => 'pdf']),
                    'params' => [
                        'id' => $id,
                        'type' => 'pdf'
                    ]
            ]);
		
			//if ($billingReconciliation->id) return $billingReconciliation->generatePDF();
		} else return $this->redirect('/personal/billing/acts');
	}
    
    public function actionBillingList($id=0,$tarifId=null){
        $confirmList=Billing::find()->with('company')->where(['isDepositAct'=>0])->orderBy(['id'=>SORT_DESC]);
        
        $model=new Billing();
         
        if(isset($_GET['Billing'])){
            $params=$_GET['Billing'];
            
            if($params['companyId']){
                $uid=ArrayHelper::map(Company::find()->where(['forDelete'=>0])->andWhere(['like', 'nameShort', $params['companyId']])->All(), 'id', 'id');
                $confirmList->andWhere(['companyId'=>$uid]);
                $model->companyId=$params['companyId'];
            }
            unset($params['companyId']);
            foreach($params as $key=>$el){
                if(($el!='all')){
                    $confirmList->andWhere(['like', $key, $el]);
                    $model->$key=$el;    
                } 
            }
        }
        
        
        $dataProvider = new ActiveDataProvider([
            'query' =>$confirmList,
        ]);
        
        return $this->render('billingList', [
            'model'=>$model,
            'dataProvider'=>$dataProvider,
        ]);
	}
    
    public function actionDocs($id=0,$tarifId=null){
        $balance=Billing::balanceByCompanyId($id);
        $model=Company::companyById($id);
        $confirmList=Billing::docsByCompanyIdProvider($id);
        
        if($tarifId)    $confirmList=$confirmList->andWhere(['tarifId'=>$tarifId]);
        
        $dataProvider = new ActiveDataProvider([
            'query' =>$confirmList,
        ]);
        
        return $this->render('docs', [
            'balance' => $balance,
            'model'=>$model,
            'dataProvider'=>$dataProvider,
        ]);
	}
    
    public function actionReceipt($id=0,$act=false){
        Billing::generatePdf($id,$act);
    }
    
    public function actionCompany($id=null) {
        $balance=Billing::balanceByCompanyId($id);
        $model=Company::companyById($id);
        $confirmList=Billing::confirmByCompanyIdProvider($id);
        
        if(!$modelTarifList=TarifList::companyTarifFull($id)){
            TarifList::currentTarif($id,true);
            $modelTarifList=TarifList::companyTarifFull($id);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' =>$confirmList,
            'pagination' => [
                'pageSize' => 5,
            ],
        ]);
        
        if($post=Yii::$app->request->post('TarifList')){
            $modelTarifList->attributes=$post;
            if($modelTarifList->validate() && $modelTarifList->save()){
                Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
            }
            else    Yii::$app->session->setFlash('danger', 'Ошибка сохранения данных');
        }
        
        return $this->render('company', [
            'balance' => $balance,
            'model'=>$model,
            'dataProvider'=>$dataProvider,
            'modelTarifList'=>$modelTarifList
        ]);
    }
    
    public function actionView($id=0){
        $model=Billing::oneByIdFull($id);
        return $this->render('view', [
            'model'=>$model,
            'typeList'=>Billing::$typeList,
            'statusList'=>Billing::$statusList,
            'writtenList'=>Billing::$writtenStatusList,
        ]);
    }
    
    public function actionHistorytarif($id=null){
        $model=Company::companyById($id);
        $tarifList=TarifList::itemListByCompanyIdProvider($id);

        $dataProvider = new ActiveDataProvider([
            'query' =>$tarifList->orderBy(['id' => SORT_DESC]),
        ]);
        
        return $this->render('historyTarif', [
            'model'=>$model,
            'dataProvider'=>$dataProvider,
        ]);
    }
    
    public function actionHistoryvictory($id=null){
        $model=Company::companyById($id);
        $victoryList=TarifList::victoryListAll($id);
        

        foreach($victoryList as $el){
            $attr=[];
            foreach($el as $key=>$e)  $attr[$key]=$e;
            $data[]=$attr;
        }

        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => array_keys($attr),
            ],
        ]);
        
        return $this->render('historyVictory', [
            'model'=>$model,
            'dataProvider'=>$provider,
        ]);
    }

    public function actionDelete($id){
        $model = Billing::oneById($id);
        $cid=$model->companyId;
        if($model->delete()) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
        $this->redirect('/billing/list?id='.$cid);
    }
    
    public function actionDeletereceipt($id){
        $model = Billing::oneById($id);
        BillingReceipt::deleteAll(['id'=>$model->receiptId]);  
        Billing::updateAll(['receiptId'=>0], ['id'=>$model->id]);  

        Yii::$app->session->setFlash('success', 'Данные успешно удалены');
        $this->redirect('/billing/update?id='.$model->id);
    }
    
    public function actionUpdate($id){
        $model=Billing::oneByIdFull($id);
        foreach(Billing::$methodList as $key=>$el)    $methodList[$key]=$el['name'];
        $userList=[0=>'Не выбрано']+THelper::cmap(User::find()->where(['companyId'=>$model->companyId])->All(), 'id', ['name','lastName'],' ');

        return $this->render('edit', [
            'model'=>$model,
            'typeList'=>Billing::$typeList,
            'statusList'=>Billing::$statusList,
            'writtenList'=>Billing::$writtenStatusList,
            'methodList'=>$methodList,
            'userList'=>$userList,
			'isLockEdit' => ($model->numberOrder > 0 && $model->dateBilling) // Если счет имеет номер платежного поручения и дату счета платежного поручения то тогда запретить редактировать счет
        ]);
    }
    
    public function actionCreate($id=null){
        $model=new Billing();
        $model->userCreatorId=Yii::$app->user->id;
        $model->companyId=$id;
        $model->price=Billing::$minPrice;
        
        foreach(Billing::$methodList as $key=>$el)    $methodList[$key]=$el['name'];
        return $this->render('edit', [
            'model'=>$model,
            'typeList'=>Billing::$typeList,
            'writtenList'=>Billing::$writtenStatusList,
            'statusList'=>Billing::$statusList,
            'methodList'=>$methodList,
			'isLockEdit' => false
        ]);
    }
    
    public function actionEdit($id=0){
		
        if($post=Yii::$app->request->post('Billing')){
            if(!$model=Billing::oneByIdFull($id)){
                $model=new Billing();
            } else {
				// Если счет имеет номер платежного поручения и дату счета платежного поручения то тогда запретить редактировать счет
				if (($model->numberOrder > 0 && $model->dateBilling)) {
					$this->redirect('/billing/view/'.$model->id);
				}
			}
			
            $model->attributes=$post;
            
            if($model->status==1){
                $model->confirm=true;
                
                if($model->type==0 && $model->methodId==1){
                    if(!$model->numberOrder || !$model->numberOrderDate){
                        Yii::$app->session->setFlash('danger', 'Номер платежного поручения и дата должны быть заполнеными');
                        return $this->redirect('/billing/update/'.$model->id); 
                    }
                }
            }   
            
            if(($model->status==1) && (!$model->userBillingId) && (!$model->id))  $model->userBillingId=Yii::$app->user->id;

            $modelReceipt=new BillingReceipt();
            if($modelReceipt->file = UploadedFileCustom::getInstanceByName('file')){
                    if($modelReceipt->file->tempName){
                        $modelReceipt->sha1 = sha1_file($modelReceipt->file->tempName).mt_rand();
    
                        $path = sprintf('/receipt/%s', $modelReceipt->sha1);
                        
                        $modelReceipt->file->saveAs($path, $modelReceipt->file->name, false);

                        $modelReceipt->name = $modelReceipt->file->name;
                        $modelReceipt->size = filesize($modelReceipt->file->tempName);
                        $modelReceipt->mime = $modelReceipt->file->type;
                        $modelReceipt->userId = $post['userCreatorId'];
                        $modelReceipt->companyId = $post['companyId']; 
                        if($modelReceipt->save())    $model->receiptId=$modelReceipt->id;
                    }
            }

            if($model->save())  Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
            else    Yii::$app->session->setFlash('danger', 'Ошибка сохранения данных');
        }
        $this->redirect('/billing/view/'.$model->id);
    }
    
    public function actionReceiptview($id=null){
        $url=BillingReceipt::generateUrlBySha($id);
        $doc=BillingReceipt::oneById($id);

        header("Content-Type: image/png");
        header("Content-Length: " . filesize($doc->size));
        
        $fp = fopen($url, 'rb');
        fpassthru($fp);
        exit;
    }
    
    public function actionReceiptdownload($id=null){
        $url=BillingReceipt::generateUrlBySha($id);
        $doc=BillingReceipt::oneById($id);

        header(sprintf("Content-Type: %s", $doc->mime));
        header('Content-Description: File Transfer');
        header(sprintf('Content-Disposition: attachment; filename= %s', $doc->name));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header(sprintf('Content-Length: %s', $doc->size));

        readfile($url);
        exit;
            
    }   
    
	public function actionRebuild($id = 0) {
		if($id > 0 && is_numeric($id)) {
			$fileModel = QueueFiles::find()->where(['targetId' => $id, 'method' => 'actsGenerate'])->with(['queue'])->one();
			if($post = Yii::$app->request->post()){
				ActionLogs::addLog(['log'=>'Запрос на перефромирование Закрывающего документа. Причина:'.$post['description'], 'model' => 'QueueFiles','targetId' => $id,'action' => 'update']);
				QueueFiles::allIncVersion(['targetId' => $id]);
				return $this->redirect('/billing/docsact/');
			}
			Yii::$app->session->setFlash('warning', Yii::t('controller', 'Для подтверждения операции заполните причину!'));
			return $this->render('../tender/rebuild',['model' => $fileModel]);
		}
		return false;
	}
	
    public function actionDocsact(){
        $model=new Billing();
        $query=Billing::docsListProvider()->orderBy(['id'=>SORT_DESC]);

        $query->andWhere(['isDepositAct'=>isset($_GET['deposit'])?1:0]);
        
        if(isset($_GET['Billing'])){
            if($_GET['Billing']['companyId']){
                $cid=ArrayHelper::map(Company::find()->where(['forDelete'=>0])->andWhere(['like', 'nameShort', $_GET['Billing']['companyId']])->All(), 'id', 'id');
                $query->andWhere(['companyId'=>$cid]);
                $model->companyId=$_GET['Billing']['companyId'];
            }
            unset($_GET['Billing']['companyId']);
            
            foreach($_GET['Billing'] as $key=>$el){
                if($el){
                    $query->andFilterWhere(['like', $key, $el]);
                    $model->$key=$el;    
                }
            }
        }
        if(isset($_GET['dateBilling']) && $_GET['dateBilling']) $query->andFilterWhere(['>', 'dateBilling', $_GET['dateBilling']]);
        if(isset($_GET['dateEnd']) && $_GET['dateEnd']) $query->andFilterWhere(['<', 'dateEnd', $_GET['dateEnd']]);
        
		$dataProvider = new ActiveDataProvider([
            'query' =>$query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('acts', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }
    
    public function actionDocsonpay(){
        $model=new Billing();
        $query=Billing::docsOnPayProvider()->with();
        
        if(isset($_GET['Billing'])){
            if($_GET['Billing']['companyId']){
                $cid=ArrayHelper::map(Company::find()->where(['like', 'nameShort', $_GET['Billing']['companyId']])->All(), 'id', 'id');
                $query->andWhere(['companyId'=>$cid]);
                $model->companyId=$_GET['Billing']['companyId'];
            }
            unset($_GET['Billing']['companyId']);
            
            foreach($_GET['Billing'] as $key=>$el){
                if($el){
                    $query->andFilterWhere(['like', $key, $el]);
                    $model->$key=$el;    
                }
            }
        }
        if(isset($_GET['dateBilling']) && $_GET['dateBilling']) $query->andFilterWhere(['>', 'dateBilling', $_GET['dateBilling']]);
        if(isset($_GET['dateEnd']) && $_GET['dateEnd']) $query->andFilterWhere(['<', 'dateEnd', $_GET['dateEnd']]);
        
		$dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);

        return $this->render('onPay', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }
    
    public function actionDocsgenerate($type=null,$action='F'){
        Billing::docsGenerate($type,$action);
    }
    
    public function actionGenerate($id = 0, $act = false, $stamp = true){
        //$docs[]=$this->actsGenerate($id, $stamp);
        //return $this->actsRender($docs);     
        //return Billing::actsGenerate($id, $stamp); 
        
        Billing::addActsToQueue([
            'id' => $id,
            'stamp' => $stamp
        ]);
        
        return $this->redirect(Yii::$app->request->referrer);
    }
    
    public function actionGenerateOne($id = 0, $act = false)
    {
        QHelper::buildDocument([
            'targetId' => $id,
            'class' => 'common\models\Billing',
            'classAlias' => 'BillingAct',
            'method' => 'generateDocument',
            'description' => Yii::t('controller', 'Акт и счет фактура № {id}', ['id' => $id]),
            'params' => [
                'id' => $id,
                'act' => $act,
                'action' => 'F'
            ]
        ]);

        return $this->redirect(Yii::$app->request->referrer);
        
        //$result['actHtml']=Billing::generateDocument($id,$act,'I');
    }        
    
    private function actsRender($data = [], $action = 'I'){
        require_once Yii::getAlias('@backend').'/modules/CRM/controllers/PHPExcel/MPDF7/mpdf.php';
        $stylesheet =file_get_contents(Yii::getAlias('@frontend').'/web/css/pdf.css'); /*подключаем css*/
        
        $mpdf = new \mPDF('utf-8', 'A4');
        $mpdf -> SetTopMargin(5);
        $mpdf->SetAutoPageBreak(true, 0);
        $mpdf->list_indent_first_level = 0; 
        $mpdf->WriteHTML($stylesheet, 1);
        
        foreach($data as $docs) {
            if($docs['actHtml']){
                $mpdf->AddPage('P');
                $mpdf->WriteHTML($docs['actHtml']);
            }
            if($docs['sfHtml']) {
                $mpdf->AddPage('L');
                $mpdf->WriteHTML($docs['sfHtml']);
            }
            if($docs['aiHtml']) {
            	$mpdf->AddPage('P');
            	$mpdf->WriteHTML($docs['aiHtml']);
            }
            if($docs['acts']){
                foreach($docs['acts'] as $el){
                    $mpdf->AddPage('P');
                    $mpdf->WriteHTML($el['act']);
                    if($el['sf_a']){
                        $mpdf->AddPage('L');
                        $mpdf->WriteHTML($el['sf_a']);
                    }
                }
            }    
        }

        if($action=="I")    return $mpdf->Output('Акты и счета '.date('Y-m-d').'.pdf', 'I'); 
        else if($action=="F"){
            $model=Billing::oneById($id);
            $path=Yii::getAlias('@backend').'/web/docs/'.$model->companyId.'/';
            if (!file_exists($path)) mkdir($path, 0755);
                
            $name='Act_vipolnennih_rabot_Schet_factura_'.$model->tarifList->dateStart.'_'.$model->tarifList->dateEnd.'.pdf';
            $mpdf->Output($path.$name, 'F');  
            return $path.$name;
        }
    }
    
    private function actsGenerate($id=null,$stamp=true){
        $result=[];
        
        $result['actHtml']=Billing::generateDocument($id,true,'H',$stamp);
        $result['sfHtml']=Billing::generateDocument($id,false,'H',$stamp);
        $result['aiHtml']=Billing::generateDocument($id,false,'H',$stamp, true);
        
        if($actsList=BillingDepositActs::listByBillingId($id)){
            foreach($actsList as $el){
                $result['acts'][]=Billing::generateDepositDocument($el->actId,false,'H',$stamp);
            }
        }
        else if(!$result['actHtml'] && !$result['sfHtml'] && !$actsList){
            if($actsList=Billing::actsByTarif($id)){ 
                foreach($actsList as $el){
                    $result['acts'][]=Billing::generateDepositDocument($el->id,false,'H',$stamp);
                }
            }
        }
        else $result['acts']=[];
        
        return $result;
    }
    
    public function actionViewdoc($id=0){
        $model=Billing::oneByIdFull($id);
        $modelTarifList=TarifList::oneById($model->tarifId);
        $userList=THelper::cmap(User::find()->where(['companyId'=>$model->companyId])->All(), 'id', ['name','lastName'],' ');

        return $this->render('viewDoc', [
            'model'=>$model,
            'modelTarifList'=>$modelTarifList,
            'userList'=>$userList
        ]);
    }     
    
    public function actionDocsdelivery($id=null){
        if($post=Yii::$app->request->post()){
            $pathToFile=[];
            
            $docs[]=$this->actsGenerate($id);
            $pathToFile[]=$this->actsRender($docs,'F');

			//print_r($post);
			//print_r($pathToFile);
			//exit();
			
			
            
            if($pathToFile){
                $uid=$post['emailUser']?0:$post['selectUser'];
                $emailTo=$post['emailUser'];
                
                $model=Billing::oneById($id);
                $title=Yii::t('c', 'Документы за период с ').$model->tarifList->dateStart.' по '.$model->tarifList->dateEnd;
                
                $userName=null;
                if($userModel=User::findOne($uid)){
                    $userName=$userModel->name.' '.$userModel->patronymic;
                }

				/*
                if(Delivery::createDelivery([
                    'uidFrom' =>Yii::$app->params['adminId'],
         			'uids' => $uid,
                    'emailTo' =>$emailTo,
                    'attach'=>implode(';',$pathToFile),
         			'subject' => $title,
 			        'text' => Yii::$app->controller->renderPartial('@common/mail/docs_delivered',[
                        'title'=>$title,
                        'userName'=>$userName,
                        'date'=>$model->tarifList 
                    ]),   
    	       ]))     Yii::$app->session->setFlash('success', 'Данные успешно отправлены');    
			   */
			   
				$emailsTo = [];
				if($uid != null) {
					$emailsTo[] = User::find()->where(['id' => $uid])->one()->userName;
				}
				
				if($emailTo != null) {
					$emailsFromString = explode(",", $emailTo);
					if($emailsFromString) {
						foreach($emailsFromString as $emailFromString) {
							$emailsTo[] = trim($emailFromString);
						}
					}
				}
				$emailsTo = array_unique($emailsTo);
				
				$attachments = [];
				foreach($pathToFile as $pathToSingleFile) {
					$fileName = basename($pathToSingleFile).PHP_EOL;
					$attachments[$fileName] = base64_encode(file_get_contents($pathToSingleFile));
				}
			   
				$result = Newdelivery::addMessageToDelivery(
				[
					'subject' => $title,
					'body' => Yii::$app->controller->renderPartial('@common/mail/docs_delivered',[
						'title'=> $title,
                        'userName'=> $userName,
                        'date'=> $model->tarifList 
                    ]), 
					'emailTo' =>  $emailsTo,
					'emailFrom' => User::find()->where(['id' => Yii::$app->params['adminId']])->one()->userName,
					'attachments' => $attachments,
					'projectId' => 2,
				]);
				
				if($result) {
					Yii::$app->session->setFlash('success', 'Данные успешно отправлены');
				}
            }
        }
        $this->redirect('/billing/viewdoc?id='.$id);
    }   
    
    public function actionPrint(){
        $post=Yii::$app->request->post();
        
        $blackCompany=TarifCompanyBlack::listCompanyArray(); 
        $selected=ArrayHelper::map(Billing::find()->select(['id','companyId'])->where(['id'=>$post['selection']])->andWhere(['<>','companyId',$blackCompany])->All(),'companyId','id');

        if($selected){
            if(isset($post['printing'])){
                require_once Yii::getAlias('@backend').'/modules/CRM/controllers/PHPExcel/MPDF7/mpdf.php';
                $stylesheet =file_get_contents(Yii::getAlias('@frontend').'/web/css/pdf.css'); /*подключаем css*/
                $mpdf = new \mPDF('utf-8', 'A4');
                $mpdf->list_indent_first_level = 0; 
                $mpdf->WriteHTML($stylesheet, 1);
                $mpdf->ignore_invalid_utf8 = true;
    
                foreach($selected as $id){
                    $docs[]=$this->actsGenerate($id,false);                    
                }      
                return $this->actsRender($docs);
            }
            else if(isset($post['vedomost'])){
                require_once Yii::getAlias('@backend').'/modules/CRM/controllers/PHPExcel/MPDF7/mpdf.php';
                $stylesheet =file_get_contents(Yii::getAlias('@frontend').'/web/css/pdf.css'); /*подключаем css*/
                $mpdf = new \mPDF('utf-8', 'A4');
                $mpdf->list_indent_first_level = 0; 
                $mpdf->WriteHTML($stylesheet, 1);
                $mpdf->ignore_invalid_utf8 = true;
                $mpdf -> SetTopMargin(10);
                $mpdf->SetAutoPageBreak(true, 0);
    
                $html=Billing::generateVedomost($selected,'H');
                $mpdf->AddPage('P');
                $mpdf->WriteHTML($html);  
                 
                return $mpdf->Output('Ведомость '.date('Y-m-d').'.pdf', 'I'); 
            }
            else if(isset($post['konvert_a4'])){
                require_once Yii::getAlias('@backend').'/modules/CRM/controllers/PHPExcel/MPDF7/mpdf.php';
                $stylesheet =file_get_contents(Yii::getAlias('@frontend').'/web/css/pdf.css'); /*подключаем css*/
                $mpdf = new \mPDF('utf-8', 'A4');
                $mpdf->list_indent_first_level = 0; 
                $mpdf->WriteHTML($stylesheet, 1);
                $mpdf->ignore_invalid_utf8 = true;
    
                foreach($selected as $id){
                    $html=Billing::generatePdfF7($id,'H');
                    $mpdf->AddPage('P');
                    $mpdf->WriteHTML($html);
                }      
                return $mpdf->Output('Ведомость '.date('Y-m-d').'.pdf', 'I'); 
            }
            else if(isset($post['konvert_a6'])){
                require_once Yii::getAlias('@backend').'/modules/CRM/controllers/PHPExcel/MPDF7/mpdf.php';
                $stylesheet =file_get_contents(Yii::getAlias('@frontend').'/web/css/pdf.css'); /*подключаем css*/
                
                $mpdf = new \mPDF('utf-8', 'A4');
                $mpdf->list_indent_first_level = 0; 
                $mpdf->WriteHTML($stylesheet, 1);
                $mpdf->ignore_invalid_utf8 = true;
                $mpdf -> SetTopMargin(10);
                $mpdf -> SetLeftMargin(5);
                $mpdf ->SetRightMargin(5);
                $mpdf->SetAutoPageBreak(true, 0);
                
                $companyList=[];
    
                foreach($selected as $key=>$id){
                    if(!in_array($key,$companyList)){
                        $html=Billing::generatePdfA6($id,'H');
                        $mpdf->AddPage('P');
                        $mpdf->WriteHTML($html);    
                        $companyList[]=$key;
                    }
                }      
                return $mpdf->Output('Конверты '.date('Y-m-d').'.pdf', 'I'); 
            }
            else if(isset($post['sender'])){
                Billing::updateAll(['shipping'=>1],['id'=>$selected]);
            }
            else if(isset($post['unsender'])){
                Billing::updateAll(['shipping'=>0],['id'=>$selected]);
            }
        }
        else    Yii::$app->session->setFlash('danger', 'Выберите один или несколько пунктов');
        $this->redirect('/billing/docsact');
    }
    
    public function actionShipping(){
        if($id=Yii::$app->request->get('id')){
            $model=Billing::find()->where(['id'=>$id])->one();
            $flag=$model->shipping?0:1;
            $text=$flag?'Документ помечен как отправленные':'Документ помечен как не отправленные';            
            Billing::updateAll(['shipping'=>$flag],['id'=>$id]);
            Yii::$app->session->setFlash('success', $text);
        }
        $this->redirect('/billing/docsact');
    }
}