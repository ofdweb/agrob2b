<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ArrayDataProvider;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use common\components\THelper;

use yii\helpers\Url;

use backend\models\TenderBackend;
use common\models\ActionLogs;
use common\models\TarifListJournal;
use common\models\Items;
use common\models\Currency;
use common\models\Company;
use common\models\Rates;
use common\models\Tenders;
use common\models\TendersHistory;
use common\models\Invitations;
use common\models\InvitationsByEmail;
use common\models\TenderLots;
use common\models\TarifList;
use common\models\User;
use common\models\RateDocs;
use common\models\QueueFiles;

use common\models\FtpFiles;
use common\models\UploadedFileCustom;

use common\components\Delivery as Newdelivery;

/**
 * Site controller
 */
class TenderController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
             'accessAdmin' => [
                'class' => \yii\filters\AccessControl::className(),
                'only' => ['date-offers-stop'],
                'rules' => [
                    // deny all POST requests
                    [
                        'allow' => true,
                        'verbs' => ['POST']
                    ],
                    [
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                    // everything else is denied
                ],
            ],
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
	
	private function checkParams() {
		$filterString = '';
		$_flag = false;
		
		//$companys = $companys = self::getCompanys();
		foreach (Yii::$app->request->queryParams as $k => $v) {
			if ($k != 'r' && $k != 'per-page' && $k != 'page' && $k != 'id' && $k != 'sort' && $k != 'nameProvider' && $k != 'nameContractor') {
				if (!empty($v) || $v === '0') {
					$_flag = true;
					if (mb_strpos($k, 'Mail', 0, 'UTF-8') !== false || mb_strpos($k, 'statusId', 0, 'UTF-8') !== false || mb_strpos($k, 'readed', 0, 'UTF-8') !== false) {
						$filterString .= '=$'.str_replace('Mail','',$k).'$'.$v.'/';
					} elseif (mb_strpos($k, 'date', 0, 'UTF-8') !== false) {
						$from = explode('-', $v)[0];
						$to = explode('-', $v)[1];
						$filterFrom = date('Y-m-d', strtotime($from));
						$filterTo = date('Y-m-d', strtotime($to));
						$filterString .= '>=$'.$k.'$'.$filterFrom.'/';
						$filterString .= '<=$'.$k.'$'.$filterTo.' 23*59*59/';
						continue;
					} elseif (mb_strpos($k, 'To', 0, 'UTF-8') !== false) {
						$filterString .= '<=$'.str_replace('To','',$k).'$'.str_replace(',','.',$v).'/';
						continue;
					} elseif (mb_strpos($k, 'From', 0, 'UTF-8') !== false) {
						$filterString .= '>=$'.str_replace('From','',$k).'$'.str_replace(',','.',$v).'/';
						continue;
					} else {
						$filterString .= 'LIKE$'.$k.'$-'.$v.'-/';
					}
					
				}
			}
		}
		if ($_flag) return $filterString; else return false;
	}
	
	public function actionRebuild($id = 0) {
		if($id > 0 && is_numeric($id)) {
			$fileModel = QueueFiles::find()->where(['id' => $id])->one();
			if($post = Yii::$app->request->post()){
				ActionLogs::addLog(['log'=>'Запрос на перефромирование протокола. Причина:'.$post['description'], 'model' => 'QueueFiles','targetId' => $id,'action' => 'update']);
				QueueFiles::allIncVersion(['id' => $id]);
				return $this->redirect('/tender/protocols/'.$fileModel->targetId);
			}
			Yii::$app->session->setFlash('warning', Yii::t('controller', 'Для подтверждения операции заполните причину!'));
			return $this->render('rebuild',['model' => $fileModel]);
		}
		return false;
	}
	
	public function actionProtocols($id = 0) {
		if($id > 0 && is_numeric($id)) {
			$tenderModel = TenderBackend::findOne($id);
			$model = new QueueFiles();
			$query = QueueFiles::find()->where(['targetId' => $id, 'forDelete' => 0, 'version' => 1])->orderBy(['id' => SORT_DESC])->with(['queue'])->groupBy(['params']);
			
			
			$protocolTypes = '';
			$tendersIds = [];
			
			$dataProvider = new ActiveDataProvider([
				'query' => $query,
			]);
			$models = $dataProvider->getModels();
			
			
			foreach($models as $_model) {$tendersIds[$_model->id] = $_model->targetId;}
			
			
			return $this->render('protocols',['tenderModel' => $tenderModel,'dataProvider' => $dataProvider, 'model' => $model]);
		}
		return false;
	}
	
    public function actionList($id = 0, $tarifId = null)
    {
        $model = new TenderBackend();

        $query = TenderBackend::itemListProvider();

        if ($params = Yii::$app->request->get('TenderBackend')) {
            if ($params['staffId']) {
                $uid = ArrayHelper::map(User::find()->andWhere(['like', 'name', $params['staffId']])->orWhere(['like', 'lastName', $params['staffId']])->orWhere(['like', 'patronymic', $params['staffId']])->All(), 'id', 'id');
                $query->andWhere(['staffId' => $uid]);
                $model->staffId = $params['staffId'];
            }
            unset($params['staffId']);

            if ($params['companyId']) {
                $cid = ArrayHelper::map(Company::find()->where(['forDelete' => 0])->andWhere(['like', 'name', $params['companyId']])->All(), 'id', 'id');
                $query->andWhere(['companyId' => $cid]);
                $model->companyId = $params['companyId'];
            }
            unset($params['companyId']);

            foreach ($params as $key => $el) {
                if (($el != 'all')) {
                    $query->andWhere(['like', $key, $el]);
                    $model->$key = $el;
                }
            }
        }

        if ($params = Yii::$app->request->get()) {
            if ($params['option_1'] == 1) {
                $query->andWhere(['>=', 'dateCreate', $params['date_1_start']]);
                $query->andWhere(['<=', 'dateCreate', $params['date_1_stop']]);
            }
            if ($params['option_2'] == 1 && !$params['option_4']) {
                // $tenderList=Tenders::find()->select(Tenders::tableName().'.id')
                // ->where(['>=','dateCreate',$params['date_2_start']])
                // ->andWhere(['<=','dateCreate',date('Y-m-d',strtotime($params['date_2_stop'].' + 2 day'))]);
                $company = [];
                if($params['option_4'] && $params['company_opt_4'])     $company[] = $params['company_opt_4'];
                if($params['option_3'] && $params['company_opt_3'])     $company[] = $params['company_opt_3'];
                
                if(!$company)   $company[] = ArrayHelper::getColumn(Company::itemList(), 'id');
 
                $itemList = TarifListJournal::billingCompanyStatistic($company, $params['date_2_start'], $params['date_2_stop']);
                
                foreach($itemList as $el)
                {
                    $tenderList[] = $el->lot->parentId;
                }

                if ($params['option_4'] == 1) {
                    //$uids = ArrayHelper::map(User::find()->select('id')->where(['companyId' => $params['company_opt_4']])->All(), 'id', 'id');
                    //$rateList = TenderLots::find()->select('tenderId')->where(['winId' => $uids])->All();
                    //var_dump(count($rateList));die;
                    //    $uids=ArrayHelper::map(User::find()->select('id')->where(['companyId'=>$params['company_opt_4']])->All(), 'id', 'id');
                    //$tenderList->joinWith('lots')->andWhere([TenderLots::tableName().'.winId'=>$uids]);
                }

                //$tenderList=$tenderList->All();
                //$tenderList = ArrayHelper::map($rateList, 'tenderId', 'tenderId');

                $query->andWhere([TenderBackend::tableName() . '.id' => $tenderList]);
                //$query->andWhere(['>=', 'dateUpdate', $params['date_2_start']]);
                //$query->andWhere(['<=', 'dateUpdate', $params['date_2_stop']]);
            }
            if ($params['option_3'] == 1) {
                $query->andWhere([TenderBackend::tableName() . '.companyId' => $params['company_opt_3']]);
            }
            if ($params['option_4'] == 1) {
                //$uids = ArrayHelper::map(User::find()->select('id')->where(['companyId' => $params['company_opt_4']])->All(), 'id', 'id');
                //$rateList = TenderLots::find()->select('tenderId')->where(['winId' => $uids])->All();
                //$tenderList = ArrayHelper::map($rateList, 'tenderId', 'tenderId');
                //$query->andWhere([TenderBackend::tableName() . '.id' => $tenderList]);
                $dateStart = $params['option_2'] && $params['date_2_start'] ? $params['date_2_start'] : null;
                $dateStop = $params['option_2'] && $params['date_2_stop'] ? $params['date_2_stop'] : null;
                
                $company[] = $params['company_opt_4'];
                $itemList = TarifListJournal::billingCompanyStatistic($company, $dateStart, $dateStop);
                foreach($itemList as $el)
                {
                    $tenderList[] = $el->lot->parentId;
                }
                $query->andWhere([TenderBackend::tableName() . '.id' => $tenderList]);
            }

        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionHistory($id = null)
    {
        $model = new Rates();

        $lotsList = ArrayHelper::map(TenderBackend::lotsList($id), 'id', 'id');
        $query = Rates::find()->where(['tenderId' => $lotsList])->with('user', 'doc', 'fileRate', 'rateValues')->orderBy(['id' => SORT_DESC]);

        if ($params = Yii::$app->request->get('Rates')) {
            if ($params['userId']) {
                $uid = ArrayHelper::map(User::find()->andWhere(['like', 'name', $params['userId']])->orWhere(['like', 'lastName', $params['userId']])->orWhere(['like', 'patronymic', $params['userId']])->All(), 'id', 'id');
                $query->andWhere(['userId' => $uid]);
                $model->userId = $params['userId'];
            }
            unset($params['userId']);

            foreach ($params as $key => $el) {
                if (($el)) {
                    $query->andWhere(['like', $key, $el]);
                    $model->$key = $el;
                }
            }
        }
//var_dump($lotsList);die;
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('history', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'id' => $id
        ]);
    }

    public function actionDownload($id = null)
    {
        $model = RateDocs::findOne($id);
        $url = RateDocs::pathToFile($model);

        header(sprintf("Content-Type: %s", $model->mime));
        header('Content-Description: File Transfer');
        header(sprintf('Content-Disposition: attachment; filename= %s', $model->name));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header(sprintf('Content-Length: %s', $model->size));
        header("Content-Type: application/octet-stream");

        readfile($url);
        exit;
    }

    public function actionStatistic($id = null)
    {
        $model = new TendersHistory();
        $model->tenderId = $id;

        $dataProvider = new ActiveDataProvider([
            'query' => TendersHistory::find()->where(['tenderId' => $id])->with('user')->orderBy(['id' => SORT_DESC]),
        ]);

        return $this->render('staistic', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionLots($id = null, $lot = 0)
    {
        $model = TenderBackend::lotsList($id);
        $modelLot = TenderLots::findOne($model[$lot]->lotId);
        $modelUnVictory = TarifListJournal::find()->where(['lotId' => $modelLot->id])->with('userUnVictory', 'file')->orderBy(['id' => SORT_DESC])->all();
        $tender = Tenders::findOne($id);

        $winner = [];
        if ($modelLot->winId) $winner = $modelLot->getWinnerInfo();

        $modelFile = new FtpFiles();
        $modelFile->scenario = FtpFiles::CATEGORY_FILES;

        return $this->render('lots', [
            'model' => $model,
            'lot' => $lot,
            'winner' => $winner,
            'modelUnVictory' => $modelUnVictory,
            'dimensions' => Items::getForSelect(93),
            'currency' => Currency::getAllForSelect(),
            'modelLot' => $modelLot,
            'services' => Items::getForSelect(153),
            'tender' => $tender,
            'modelFile' => $modelFile
        ]);
    }

    public function actionView($id = null)
    {
        $model = TenderBackend::oneById($id);
        $modelInvitations = Invitations::find()->where(['tenderId' => $id])->with('company')->all();
        $modelInvitations['emails'] = InvitationsByEmail::find()->where(['tenderId' => $id])->all();

        $modelFile = new FtpFiles();
        $modelFile->scenario = FtpFiles::CATEGORY_FILES;

		$page = Yii::$app->request->queryParams['page']?Yii::$app->request->queryParams['page']:1;
		
		$itsAll = false;
		$pageSize = 10;
		
		//>=$dateOrder$2016-11-01/<=$dateOrder$2016-11-24 23*59*59/ // example
		//pre(self::checkParams());
		$data = Newdelivery::getMessageInfo(
		[
			'filter' => self::checkParams().'=$model$Tender/=$targetId$'.$id.'/=$projectId$2',
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
		$modelType = Yii::$app->request->getQueryParam('model', '');
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
			'model' => $modelType,
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
		$userModel = User::find()->where(['id' => $id])->one();

        return $this->render('view', [
			
			'userModel' => $userModel,
			'provider' => $provider,
			'searchModel' => $searchModel,
		
            'model' => $model,
            'dimensions' => Items::getForSelect(93),
            'currency' => Currency::getAllForSelect(),
            'modelInvitations' => $modelInvitations,
            'services' => Items::getForSelect(153),
            'modelFile' => $modelFile
        ]);
    }
    
    public function actionDateOffersStop($id = null)
    {
        $model = Tenders::findOne($id);

        if($model->status != Tenders::STATUS_ACTIVE) {
            Yii::$app->session->setFlash('danger', Yii::t('controller', 'Не удалось продлить заявку. Данная заявка неактивна.'));
        } elseif ($post = Yii::$app->request->post()) {
            $file = new FtpFiles();
            $file->scenario = FtpFiles::CATEGORY_FILES;
            if (($file->file = UploadedFileCustom::getInstance($file, 'file')) && $file->validate()) {
                if ($file->file->tempName) {
                    $file->file->server = $file->ftpServersDefault[FtpFiles::CATEGORY_FILES];
                    $file->sha1 = sha1_file($file->file->tempName);

                    $path = sprintf('/%s/%s', FtpFiles::CATEGORY_FILES, $file->sha1);

                    if ($file->file->saveAs($path, $file->file->name, false)) {
                        // если стоит модуль юзаем его, иначе берем инфу из сопроводительной инфы
                        if (function_exists('finfo_open')) {
                            $finfo = finfo_open(FILEINFO_MIME);
                            if ($finfo) {
                                $file->mime = finfo_file($finfo, $file->file->tempName);
                            }
                        } else {
                            $file->mime = $file->file->type;
                        }

                        $file->title = $post['FtpFiles']['title'];
                        $file->name = $file->file->name;
                        $file->size = filesize($file->file->tempName);
                        $file->userId = Yii::$app->user->getId();
                        $file->companyId = $companyId;
                        $file->ftpServer = $file->file->server;
                        $file->category = FtpFiles::CATEGORY_FILES;

                        if ($file->save()) {
                            Yii::$app->session->setFlash('success', Yii::t('controller', 'Заявка успешно продлена'));
                            //return $this->redirect(Yii::$app->UrlManager->createUrl(sprintf('/personal/tender/edit-sell?id=%s&a=files', $tender->id)));

                            Tenders::updateAll(['dateOffersStop' => $post['Tenders']['dateOffersStop']], ['id' => $id]);
                            Tenders::updateAll(['dateOffersStop' => $post['Tenders']['dateOffersStop']], ['parentId' => $id]);
                        }
                    } else {
                        $file->addError('file', join(', ', $file->file->errors));
                    }
                } else if (!$file->file->size) Yii::$app->session->setFlash('danger', Yii::t('controller', 'Не удалось добавить файл {file}. Превышен допустимый размер файла.', ['file' => $file->name]));
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionUnvictorylot($id = null)
    {
        $model = TenderLots::findOne($id);

        if ($post = Yii::$app->request->post()) {
            $file = new FtpFiles();
            $file->scenario = FtpFiles::CATEGORY_FILES;
            if (($file->file = UploadedFileCustom::getInstance($file, 'file')) && $file->validate()) {
                if ($file->file->tempName) {
                    $file->file->server = $file->ftpServersDefault[FtpFiles::CATEGORY_FILES];
                    $file->sha1 = sha1_file($file->file->tempName);

                    $path = sprintf('/%s/%s', FtpFiles::CATEGORY_FILES, $file->sha1);

                    if ($file->file->saveAs($path, $file->file->name, false)) {
                        // если стоит модуль юзаем его, иначе берем инфу из сопроводительной инфы
                        if (function_exists('finfo_open')) {
                            $finfo = finfo_open(FILEINFO_MIME);
                            if ($finfo) {
                                $file->mime = finfo_file($finfo, $file->file->tempName);
                            }
                        } else {
                            $file->mime = $file->file->type;
                        }

                        $file->title = $post['FtpFiles']['title'];
                        $file->name = $file->file->name;
                        $file->size = filesize($file->file->tempName);
                        $file->userId = Yii::$app->user->getId();
                        $file->companyId = $companyId;
                        $file->ftpServer = $file->file->server;
                        $file->category = FtpFiles::CATEGORY_FILES;

                        if ($file->save()) {
                            Yii::$app->session->setFlash('info', Yii::t('controller', 'Файл {file} добавлен', ['file' => $file->name]));
                            //return $this->redirect(Yii::$app->UrlManager->createUrl(sprintf('/personal/tender/edit-sell?id=%s&a=files', $tender->id)));

                            if (TarifListJournal::unActiveVictory($id, $file->id)) {
                                Tenders::updateAll(['isComplete' => 0], ['id' => $model->tenderId]);
                                TenderLots::updateAll(['winId' => 0, 'companyId' => 0, 'rateId' => 0], ['tenderId' => $model->tenderId]);
                                
                                $positionIds = ArrayHelper::getColumn(TenderLots::find()->where(['AND', ['tenderId' => $model->tenderId], ['<>', 'cloneId', 0]])->All(), 'id');
                                TenderLots::updateAll(['forDelete' => 1], ['AND', ['tenderId' => $model->tenderId, 'forDelete' => 0], ['<>', 'cloneId', 0]]);
                                Tenders::updateAll(['forDelete' => 1], ['parentId' => $model->tenderId, 'lotId' => $positionIds]);
                    
                                TendersHistory::add('unActiveVictory', $model->tenderId);
                                Yii::$app->session->setFlash('success', 'Победитель отменен');
                            } else Yii::$app->session->setFlash('danger', 'Ошибка изменения данных');
                        }
                    } else {
                        $file->addError('file', join(', ', $file->file->errors));
                    }
                } else if (!$file->file->size) Yii::$app->session->setFlash('danger', Yii::t('controller', 'Не удалось добавить файл {file}. Превышен допустимый размер файла.', ['file' => $file->name]));
            }
        }
        return $this->redirect('/tender/lots/' . $model->tenderId);
    }

    public function actionUnactive($id = null)
    {
        if ($model = TenderBackend::oneById($id)) {
            $model->status = 60;
            $model->scenario = 'status';

            if ($model->save()) Yii::$app->session->setFlash('success', 'Заявка оменена');
            else    Yii::$app->session->setFlash('danger', 'Ошибка оменены заявки');
        }
        return $this->redirect('/tender/view/' . $model->id);
    }
}