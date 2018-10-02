<?php
namespace backend\controllers;

use Yii;
use yii\base\DynamicModel;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use common\components\THelper;

use common\models\Delivery;
use common\models\User;
use common\models\Company;
use common\models\DeliveryNotSending;
use common\models\DeliveryLogs;

use common\components\Delivery as Newdelivery;

/**
 * Site controller
 */
class DeliveryController extends Controller
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
    
	
	public function actionList()
	{	           
        $model = new Delivery();
        
        $model->status = null;
        $model->sendType = null;
        $model->codeError = null;
        
        $query = Delivery::itemListProvider();
        
        if($params = Yii::$app->request->get('Delivery'))
        {
            if($params['uids']){
                $query->joinWith('user')->andWhere(['like', User::tableName().'.userName', $params['uids']]);
                $query->orWhere(['like', Delivery::tableName().'.emailTo', $params['uids']]);
                $model->uids = $params['uids'];
                unset($params['uids']);
            }
            foreach($params as $key=>$el){
                if($el!=null){
                    $query->andWhere(['like', Delivery::tableName().'.'.$key, $el]);
                    $model->$key=$el;
                }
            }

        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        
        return $this->render('list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
	}
    
    public function actionView($id = null)
	{
		if($model = Delivery::oneById($id)){
		  
          return $this->render('view', [
            'model' => $model,
		  ]);
		}
        else $this->redirect('/delivery/list');
	}
    
    public function actionSendProgram($id = null)
    {
        if($id && ($delivered = Delivery::send($id))){
            Yii::$app->session->setFlash('success', 'Cообщений успешно отправлено: ' . count($delivered));
            return $this->redirect(['/delivery/view', 'id' => $id]);
        }
        
        Yii::$app->session->setFlash('danger', 'Не удалось отправить сообщение');
        return $this->redirect('/delivery/list');
    }
    
    public function actionRestartSend($id = null)
    {
        if($model = Delivery::oneById($id)){
            $clone = new Delivery();
            $clone->attributes = $model->attributes;
            $clone->cleare();
            
            if($clone->save()){
                Yii::$app->session->setFlash('success', 'Создано новое сообщение №' . $clone->id);
                return $this->redirect(['/delivery/send-program', 'id' => $clone->id]);
            }
        }
        
        Yii::$app->session->setFlash('danger', 'Не удалось отправить сообщение');
        return $this->redirect('/delivery/list');
    }
    
	
	public function actionUpdate($id = null)
	{
		
		//die('ok bro');
		
		$model = new DynamicModel(['id', 'subject', 'body', 'companys', 'parentId', 'uids', 'emailTo', 'emailFrom', 'statusId', 'dateAdd', 'dateSend', 'priority', 'codeError', 'projectId', 'modelForTarget', 'targetId']);
		
		if(Yii::$app->request->post()) {
			if(isset(Yii::$app->request->post()['Delivery'])) {
				$deliveryData = Yii::$app->request->post()['Delivery'];
			}
			elseif(isset(Yii::$app->request->post()['DynamicModel'])) {
				$deliveryData = Yii::$app->request->post()['DynamicModel'];
			}
			
			$subject = $deliveryData['subject'];
			$body = $deliveryData['body'];
			$from = User::find()->where(['id' => $deliveryData['emailFrom']])->one()->userName;
			
			$emailTo = [];
			
			if($deliveryData['uids'][0] == 'all') {
				foreach($deliveryData['companys'] as $companyId) {
					foreach(User::find()->where(['companyId' => $companyId])->all() as $userData) {
						$emailTo[] = $userData->userName;
					}
				}
			}
			else {
				foreach($deliveryData['uids'] as $userId) {
					$emailTo[] = User::find()->where(['id' => $userId])->one()->userName;
				}
			}
			
			foreach($deliveryData['uids'] as $deliveryId) {
				$emailTo[] = User::find()->where(['id' => $deliveryId])->one()->userName;
			}
			
			if($deliveryData['emailTo'] != null) {
				$emailsFromString = explode(",", $deliveryData['emailTo']);
				if($emailsFromString) {
					foreach($emailsFromString as $emailFromString) {
						$emailTo[] = trim($emailFromString);
					}
				}
			}

			$emailTo = array_unique($emailTo);
			
			$statusId = $deliveryData['statusId'];
			$priority = $deliveryData['priority'];
				
			$deliveryData = [
				'subject' => $subject,
				'body' => $body,
				'emailTo' =>  $emailTo,
				'priority' => 0,
				'statusId' => $statusId,
				'priority' => $priority,
				'emailFrom' => $from,
				'projectId' => 2,
			];
			
			if($id) {
				$deliveryData['id'] = $id;
			}
			
			$result = Newdelivery::addMessageToDelivery($deliveryData);
			
			if(json_decode($result)->id) {
				if(json_decode($result)->id) {
					Yii::$app->session->setFlash('success', 'Сообщение было сохранено');
				}
				else {
					Yii::$app->session->setFlash('danger', 'Не удалось сохранить сообщение');
				}
				return $this->redirect(['delivery/dellivery-message', 'id' => json_decode($result)->id]);
			}
			else {
				if($result->error != 'cant_update_status') {
					throw new \yii\base\ErrorException('Нельзя изменить статус данного письма');
				}
				else {
					throw new \yii\base\ErrorException('Во время добавления письма в очередь на рассылку произошла ошибка.');
				}
			}
			
			// Продолжить тут. Добавить компонент.
			
		}
		elseif($id != null && is_numeric($id)) {
			$data = Newdelivery::getMessageInfo(
			[
				'id' => $id,
			]);
			
			$data = $data[0];
			
			$subject = $data->subject;
			$body = $data->body;
			$emailTo = [];
			$statusId = $data->statusId;
			$dateAdd = $data->dateAdd;
			$dateSend = $data->dateSend;
			$priority = $data->priority;
			$codeError = $data->codeError;
			$projectId = $data->projectId;
			$modelForTarget = $data->model;
			$targetId = $data->targetId;
			
			
			if($data->parentId) {
				$parentData = $data->parents[0];
				$subject = $parentData->subject;
				$body = $parentData->body;
				$emailTo[] = $data->emailTo;
				$emailFrom = $data->emailFrom;
				$projectId = $parentData->projectId;
				$deliveryHash = $data->deliveryHash;
				$deliveryMesId = $data->mesId;
				$modelForTarget = $parentData->model;
				$targetId = $parentData->targetId;
			}
			elseif($data->isGroup){
				foreach($data->childs as $childData) {
					$emailTo[] = $childData->emailTo;
					$emailFrom = $childData->emailFrom;
					$deliveryHash = $childData->deliveryHash;
					$deliveryMesId = $childData->mesId;
				}
			}
			else {
				$emailTo[] = $data->emailTo;
				$emailFrom = $data->emailFrom;
				$deliveryHash = $data->deliveryHash;
				$deliveryMesId = $data->mesId;
			}
			
			$companys = [];
			$parentId = [];
			$uids = [];
			$model = new DynamicModel(compact('id', 'subject', 'body', 'companys', 'parentId', 'uids', 'emailTo', 'emailFrom', 'statusId', 'dateAdd', 'dateSend', 'priority', 'codeError', 'projectId', 'modelForTarget', 'targetId'));
			$model->emailTo = implode(', ', $emailTo);
			
			
			
			/*
			$model->subject = $subject;
			$model->body = $body;
			$model->emailFrom = $emailFrom;
			$model->statusId = $statusId;
			$model->dateAdd = $dateAdd;
			$model->dateSend = $dateSend;
			$model->priority = $priority;
			$model->codeError = $codeError;
			$model->projectId = $projectId;
			$model->modelForTarget = $modelForTarget;
			$model->targetId = $targetId;
			*/
		}
		
		/*
		
        if(!$model = Delivery::oneById($id)){
            $model = new Delivery;
  		}
        
        if($model->isGroup && $model->id){
            $model->uids = ArrayHelper::getColumn($model->childs, 'uids');
        }

        if($model->status == 1){
            Yii::$app->session->setFlash('error', 'Вы не можете вносить изменения. Данное письмо уже было отправлено');
            return $this->redirect(['/delivery/view', 'id' => $model->id]);
        }
        else if(!$model->groupChildStatus('unsend')){
            Yii::$app->session->setFlash('error', 'Вы не можете вносить изменения. Все письма данной группы были отправлены');
            return $this->redirect(['/delivery/view', 'id' => $model->id]);
        }

        if($model->load(Yii::$app->request->post())){
            
            
            if($model->uids[0] == 'all') {
                $model->uids = ArrayHelper::getColumn(User::find()->where(['companyId' => $model->companys])->All(), 'id');
            }

            if($model->save()){
                Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
            }  
            else{
                Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
            }
            
            return $this->redirect(['/delivery/view', 'id' => $model->id]);
        }
		*/
        
        $options = [
            'prefSuf' => [
                'userName' => ['pref' => '(', 'suf' => ')'],
            ]
        ];
        
        $users = THelper::cmap(User::find()->All(), 'id', ['name', 'userName'], ' ', $options);
        $companys = ArrayHelper::map(Company::find()->where(['forDelete'=>0])->All(), 'id', 'nameShort');

		return $this->render('_form', [
			'model' => $model,
            'users' => $users,
            'companys' => $companys,
		]);
	}
	
    
    public function actionNotdelivered(){
        $model=new DeliveryNotSending();
        $query=DeliveryNotSending::find()->orderBy(["dateAdd" => SORT_DESC ]);
        
        if(isset($_GET['DeliveryNotSending'])){
            $params=$_GET['DeliveryNotSending'];
            foreach($params as $key=>$el){
                if($el!=null){
                    $query->andWhere(['like', $key, $el]);
                    $model->$key=$el;
                }
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);
        
        return $this->render('notDelivered', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }
    
    public function actionNotdelivview($id='')
	{
		if($id && is_numeric($id)){
			if($model=DeliveryNotSending::findOne($id)){
			    $modelDeliv=Delivery::deliveryById($model->delivId);
    			return $this->render('notDeliveredView', [
    				'model' => $model,
                    'modelDeliv'=>$modelDeliv
    			]);
            }
            else $this->redirect('/delivery/list');
		}
	}
	
    public function actionUnactive($id = null){
        if(Delivery::statusUpdate($id, 5)){
            Yii::$app->session->setFlash('success', 'Статус успешно изменен');
        }
        else{
            Yii::$app->session->setFlash('danger', 'Невозможно изменить статус. Сообщение уже отправлено');
        }
        
        $this->redirect('/delivery/list');
    }
    
    public function actionLogs($id = null){
        $modelLog = new DeliveryLogs();
        $model = Delivery::oneById($id);
        $query = DeliveryLogs::itemListProvider();
        
        $dataProvider = new ActiveDataProvider([
            'query' =>$query->where(['deliveryId' => $id]),
        ]);
        
        return $this->render('logs', [
            'dataProvider' => $dataProvider,
            'model' => $model,
            'modelLog' => $modelLog
	    ]);
    }
	
	private function checkParams() {
		$filterString = '';
		$_flag = false;
		
		//$companys = $companys = self::getCompanys();
		foreach (Yii::$app->request->queryParams as $k => $v) {
			if ($k != 'r' && $k != 'per-page' && $k != 'page' && $k != 'id' && $k != 'sort' && $k != 'nameProvider' && $k != 'nameContractor') {
				if (!empty($v) || $v === '0') {
					$_flag = true;
					if (mb_strpos($k, 'Mail', 0, 'UTF-8') !== false || mb_strpos($k, 'statusId', 0, 'UTF-8') !== false || mb_strpos($k, 'codeError', 0, 'UTF-8') !== false || mb_strpos($k, 'readed', 0, 'UTF-8') !== false/* || mb_strpos($k, 'emailTo', 0, 'UTF-8') !== false*/) {
						$filterString .= '=$'.str_replace('Mail','',$k).'$'.$v.'/';
					}
					elseif (mb_strpos($k, 'Delivery', 0, 'UTF-8') !== false || mb_strpos($k, 'statusId', 0, 'UTF-8') !== false || mb_strpos($k, 'codeError', 0, 'UTF-8') !== false || mb_strpos($k, 'readed', 0, 'UTF-8') !== false/* || mb_strpos($k, 'emailTo', 0, 'UTF-8') !== false*/) {
						$filterString .= '=$'.str_replace('Delivery','',$k).'$'.$v.'/';
					}
					elseif (mb_strpos($k, 'date', 0, 'UTF-8') !== false) {
						$from = explode('-', $v)[0];//idSoomeParam
						$to = explode('-', $v)[1];
						$filterFrom = date('Y-m-d', strtotime($from));
						$filterTo = date('Y-m-d', strtotime($to));
						$filterString .= '>=$'.$k.'$'.$filterFrom.'/';
						$filterString .= '<=$'.$k.'$'.$filterTo.' 23*59*59/';
						continue;
					} elseif (mb_strpos($k, 'To', 0, 'UTF-8') !== false && mb_strpos($k, 'mail', 0, 'UTF-8') === false) {
						$filterString .= '<=$'.str_replace('To','',$k).'$'.str_replace(',','.',$v).'/';
						continue;
					} elseif (mb_strpos($k, 'From', 0, 'UTF-8') !== false && mb_strpos($k, 'mail', 0, 'UTF-8') === false) {
						$filterString .= '>=$'.str_replace('From','',$k).'$'.str_replace(',','.',$v).'/';
						continue;
					} else {
						$filterString .= 'LIKE$'.$k.'$'.$v.'/';
					}
				}
			}
		}
		if ($_flag) return $filterString; else return false;
	}
	
	public function actionUserDelivery($id) {
		
		$page = Yii::$app->request->queryParams['page']?Yii::$app->request->queryParams['page']:1;
		
		$itsAll = false;
		$pageSize = 10;
		
		$userData = User::find()->where(['id' => $id])->one();
		
		//>=$dateOrder$2016-11-01/<=$dateOrder$2016-11-24 23*59*59/ // example
		//pre(self::checkParams());
		$data = Newdelivery::getMessageInfo(
		[
			'filter' => self::checkParams().'=$emailTo$'.$userData->userName.'/=$projectId$2',
			'page' => $page,
			'pageSize' => $pageSize,
		]);
		
		if (count($data) <= $pageSize) {
			$itsAll = true;
		}
		
		$result = [];
		$i = $page*$pageSize - $pageSize;
		$x = $page == 1 || empty($page)?0:$page*$pageSize-$pageSize;
		if ($page > 1) {
			for ($y = 0; $y < $x; $y++) {
				$result[$y] = [];
			}
		}
		foreach ($data as $key => $item) {
			$result[$i] = $item;
			$i++;
		}
		
		$provider = new ArrayDataProvider([
			'allModels' => $result,
			'pagination' => ['pageSize' => $pageSize],
		]);
		
		
		$idMail = Yii::$app->request->getQueryParam('idMail', '');
		$subject = Yii::$app->request->getQueryParam('subject', '');
		$body = Yii::$app->request->getQueryParam('body', '');
		$statusId = Yii::$app->request->getQueryParam('statusId', '');
		//$dateAdd = Yii::$app->request->getQueryParam('dateAdd', '');
		//$dateSend = Yii::$app->request->getQueryParam('dateSend', '');
		$priority = Yii::$app->request->getQueryParam('priority', '');
		$codeError = Yii::$app->request->getQueryParam('codeError', '');
		//$emailTo = Yii::$app->request->getQueryParam('emailTo', '');
		$emailFrom = Yii::$app->request->getQueryParam('emailFrom', '');
		$projectId = Yii::$app->request->getQueryParam('projectId', '');
		$deliveryHash = Yii::$app->request->getQueryParam('deliveryHash', '');
		$mesId = Yii::$app->request->getQueryParam('mesId', '');
		$model = Yii::$app->request->getQueryParam('model', '');
		$targetId = Yii::$app->request->getQueryParam('targetId', '');
		$parentId = Yii::$app->request->getQueryParam('parentId', '');
		$isGroup = Yii::$app->request->getQueryParam('isGroup', '');
		$readed = Yii::$app->request->getQueryParam('readed', '');
		
		$searchModel = [
			'idMail' => $idMail,
			'subject' => $subject,
			'body' => $body,
			'statusId' => $statusId,
			//'dateAdd' => $dateAdd,
			//'dateSend' => $dateSend,
			'priority' => $priority,
			'codeError' => $codeError,
			//'emailTo' => $emailTo,
			'emailFrom' => $emailFrom,
			'projectId' => $projectId,
			'deliveryHash' => $deliveryHash,
			'mesId' => $mesId,
			'model' => $model,
			'targetId' => $targetId,
			'parentId' => $parentId,
			'isGroup' => $isGroup,
			'readed' => $readed,
		];
		if ($itsAll) {
			$counter = $page * $pageSize;
		} else {
			$counter = $page * $pageSize * 2;
		}
		$provider->setTotalCount($counter);
		$model = User::find()->where(['id' => $id])->one();
		return $this->render('userDelivery', ['model' => $model, 'provider' => $provider, 'searchModel' => $searchModel]);
		
	}
	
	public function actionDelliveryMessage($id = null) {
		
		$data = Newdelivery::getMessageInfo(
		[
			'id' => $id,
		]);
        
        return $this->render('deliveryMessage', [
			'model' => $data[0]
        ]);
		
	}
	
	public function actionDeliveryLog($id = null){
		
		$data = Newdelivery::getMessageInfo(
		[
			'id' => $id,
		]);
		
		$subject = $data->subject;
		$body = $data->body;
		$emailTo = [];
		$statusId = $data->statusId;
		$dateAdd = $data->dateAdd;
		$dateSend = $data->dateSend;
		$priority = $data->priority;
		$codeError = $data->codeError;
		$projectId = $data->projectId;
		$modelForTarget = $data->model;
		$targetId = $data->targetId;
			
		$isGroup = false;
		$parentId = false;
			
		if($data->parentId) {
			$parentId = $data->parentId;
			$parentData = $data->parents[0];
			$subject = $parentData->subject;
			$body = $parentData->body;
			$emailTo[] = $data->emailTo;
			$emailFrom = $data->emailFrom;
			$projectId = $parentData->projectId;
			$deliveryHash = $data->deliveryHash;
			$deliveryMesId = $data->mesId;
			$modelForTarget = $parentData->model;
			$targetId = $parentData->targetId;
		}
		elseif($data->isGroup){
			$isGroup = $data->isGroup;
			foreach($data->childs as $childData) {
				$emailTo[] = $childData->emailTo;
				$emailFrom = $childData->emailFrom;
				$deliveryHash = $childData->deliveryHash;
				$deliveryMesId = $childData->mesId;
			}
		}
		else {
			$emailTo[] = $data->emailTo;
			$emailFrom = $data->emailFrom;
			$deliveryHash = $data->deliveryHash;
			$deliveryMesId = $data->mesId;
		}
        
		//pre($data);
		
		/*
        return $this->render('deliveryMessage', [
			'model' => $data[0]
        ]);
		
        $modelLog = new DeliveryLogs();
        $model = Delivery::oneById($id);
        $query = DeliveryLogs::itemListProvider();
        */
		
        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
        ]);
		
		//$model = new DynamicModel(compact('id'));
		$isNewRecord = false;
		$model = new DynamicModel(compact('id', 'subject', 'body', 'companys', 'parentId', 'uids', 'emailTo', 'emailFrom', 'statusId', 'dateAdd', 'dateSend', 'priority', 'codeError', 'projectId', 'modelForTarget', 'targetId', 'isNewRecord' ,'isGroup', 'parentId'));
        return $this->render('logs', [
			'dataProvider' => $dataProvider,
            //'dataProvider' => $dataProvider,
            'model' => $model,
            //'modelLog' => $modelLog
	    ]);
    }
	
	public function actionSendFast($id = null)
    {
		$result = Newdelivery::sendNow($id);
		$result = json_decode($result);
		
		if($result->log == 'Success') {
			Yii::$app->session->setFlash('success', 'Cообщение успешно отправлено');
            return $this->redirect(['/delivery/dellivery-message', 'id' => $id]);
		}
        
        Yii::$app->session->setFlash('danger', 'Не удалось отправить сообщение');
        return $this->redirect(['/delivery/dellivery-message', 'id' => $id]);
    }
	
	public function actionResend($id = null)
    {
		$data = Newdelivery::getMessageInfo(
		[
			'id' => $id,
		]);
		
		unset($data[0]->id);
		unset($data[0]->dateAdd);
		unset($data[0]->dateSend);
		unset($data[0]->deliveryHash);
		unset($data[0]->codeError);
		unset($data[0]->mesId);
		unset($data[0]->logs);
		unset($data[0]->statusId);
		
		if($data[0]->parentId != null) {
			$data[0]->parentId = null;
			$data[0]->subject = $data[0]->parents[0]->subject;
			$data[0]->body = $data[0]->parents[0]->body;
		}
		elseif($data[0]->isGroup == 1) {
			$emailsList = [];
			foreach($data[0]->childs as $childDelivery) {
				$emailsList[] = $childDelivery->emailTo;
			}
			$data[0]->emailTo = $emailsList;
		}
		
		if(!is_array($data[0]->emailTo)) {
			$emailTo = $data[0]->emailTo;
			$data[0]->emailTo = [];
			$data[0]->emailTo[] = $emailTo;
		}
		
		unset($data[0]->parents);
		unset($data[0]->parentId);
		unset($data[0]->childs);
		unset($data[0]->isGroup);
		
		$data[0]->priority = 2;
		
		$result = Newdelivery::addMessageToDelivery((array)$data[0]);
		$result = json_decode($result);
		if($result->id) {
			Yii::$app->session->setFlash('success', 'Cообщений было успешно продублировано');
            return $this->redirect(['/delivery/dellivery-message', 'id' => $result->id]);
		}
		else {
			Yii::$app->session->setFlash('danger', 'Не удалось отправить сообщение');
			return $this->redirect(['/delivery/dellivery-message', 'id' => $id]);
		}
		
    }
	
	public function actionCancelDelivery($id) {
		
		$data = Newdelivery::getMessageInfo(
		[
			'id' => $id,
		]);
		
		if($data[0]->statusId == 0) {
			unset($data[0]->dateAdd);
			unset($data[0]->dateSend);
			unset($data[0]->deliveryHash);
			unset($data[0]->codeError);
			unset($data[0]->mesId);
			unset($data[0]->logs);
			
			if($data[0]->parentId != null) {
				$data[0]->parentId = null;
				$data[0]->subject = $data[0]->parents[0]->subject;
				$data[0]->body = $data[0]->parents[0]->body;
			}
			elseif($data[0]->isGroup == 1) {
				$emailsList = [];
				foreach($data[0]->childs as $childDelivery) {
					$emailsList[] = $childDelivery->emailTo;
				}
				$data[0]->emailTo = $emailsList;
			}
			
			if(!is_array($data[0]->emailTo)) {
				$emailTo = $data[0]->emailTo;
				$data[0]->emailTo = [];
				$data[0]->emailTo[] = $emailTo;
			}
			
			unset($data[0]->parents);
			unset($data[0]->parentId);
			unset($data[0]->childs);
			unset($data[0]->isGroup);
			
			$data[0]->priority = 1;
			$data[0]->statusId = 5;
			
			$result = Newdelivery::addMessageToDelivery((array)$data[0]);
			$result = json_decode($result);
			if($result->id) {
				Yii::$app->session->setFlash('success', 'Cообщений было успешно отменено');
			}
			else {
				Yii::$app->session->setFlash('danger', 'Не удалось отменить сообщение');
			}
			return $this->redirect(Yii::$app->request->referrer);
		}
		
	}
	
	public function actionNewDelivery() {
		
		$page = Yii::$app->request->queryParams['page']?Yii::$app->request->queryParams['page']:1;
		
		$itsAll = false;
		$pageSize = 10;
		
		//>=$dateOrder$2016-11-01/<=$dateOrder$2016-11-24 23*59*59/ // example
		//pre(self::checkParams());
		$data = Newdelivery::getMessageInfo(
		[
			'filter' => self::checkParams().'=$projectId$2',
			'page' => $page,
			'pageSize' => $pageSize,
		]);
		
		if (count($data) <= $pageSize) {
			$itsAll = true;
		}
		
		$result = [];
		$i = $page*$pageSize - $pageSize;
		$x = $page == 1 || empty($page)?0:$page*$pageSize-$pageSize;
		if ($page > 1) {
			for ($y = 0; $y < $x; $y++) {
				$result[$y] = [];
			}
		}
		foreach ($data as $key => $item) {
			$result[$i] = $item;
			$i++;
		}
		
		$provider = new ArrayDataProvider([
			'allModels' => $result,
			'pagination' => ['pageSize' => $pageSize],
		]);
		
		
		$idMail = Yii::$app->request->getQueryParam('idMail', '');
		$subject = Yii::$app->request->getQueryParam('subject', '');
		$body = Yii::$app->request->getQueryParam('body', '');
		$statusId = Yii::$app->request->getQueryParam('statusId', '');
		$dateAdd = Yii::$app->request->getQueryParam('dateAdd', '');
		$dateSend = Yii::$app->request->getQueryParam('dateSend', '');
		$priority = Yii::$app->request->getQueryParam('priority', '');
		$codeError = Yii::$app->request->getQueryParam('codeError', '');
		$emailTo = Yii::$app->request->getQueryParam('emailTo', '');
		$emailFrom = Yii::$app->request->getQueryParam('emailFrom', '');
		$projectId = Yii::$app->request->getQueryParam('projectId', '');
		$deliveryHash = Yii::$app->request->getQueryParam('deliveryHash', '');
		$mesId = Yii::$app->request->getQueryParam('mesId', '');
		$model = Yii::$app->request->getQueryParam('model', '');
		$targetId = Yii::$app->request->getQueryParam('targetId', '');
		$parentId = Yii::$app->request->getQueryParam('parentId', '');
		$isGroup = Yii::$app->request->getQueryParam('isGroup', '');
		$readed = Yii::$app->request->getQueryParam('readed', '');
		
		$searchModel = [
			'idMail' => $idMail,
			'subject' => $subject,
			'body' => $body,
			'statusId' => $statusId,
			'dateAdd' => $dateAdd,
			'dateSend' => $dateSend,
			'priority' => $priority,
			'codeError' => $codeError,
			'emailTo' => $emailTo,
			'emailFrom' => $emailFrom,
			'projectId' => $projectId,
			'deliveryHash' => $deliveryHash,
			'mesId' => $mesId,
			'model' => $model,
			'targetId' => $targetId,
			'parentId' => $parentId,
			'isGroup' => $isGroup,
			'readed' => $readed,
		];
		if ($itsAll) {
			$counter = $page * $pageSize;
		} else {
			$counter = $page * $pageSize * 2;
		}
		$provider->setTotalCount($counter);
		$model = User::find()->where(['id' => $id])->one();
		return $this->render('newDelivery', ['model' => $model, 'provider' => $provider, 'searchModel' => $searchModel]);
		
	}
}