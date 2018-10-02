<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use yii\helpers\Url;

use common\models\Company;
use common\models\Division;
use common\models\Okpd;
use common\models\Delivery;
use common\models\Messages;
use common\models\User;
use common\models\CompanyAddress;
use common\models\CompanyPhone;
use common\models\ActionLogs;
use common\models\Group;
use common\models\CompanyDocs;
use common\models\Rates;
use common\models\DocPatterns;
use common\models\FtpFiles;
use common\models\UploadedFileCustom;
use common\models\CompanyComment;
use common\models\DocPatternCategories;

use backend\models\TarifCompanyBlack;
use backend\models\GroupCompanyRelations;
use backend\models\RegistrationCustom;

use common\components\Delivery as Newdelivery;

/**
 * Site controller
 */
class CompanyController extends Controller
{
	public $aaa;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
			'access' => [
				'class' => \backend\behaviors\AccessBehavior::className(),
				/*
				'rules' => [
					[
						'allow' => true,
						'actions'=>['login','error'],
						'roles' => ['?'],
					],
					[
						'allow' => true,
						'roles' => ['admin'],
					],
				],
				
				
				'denyCallback' => function ( $rule , $action ) { 
					Yii::$app->getResponse()->redirect(Yii::$app->UrlManager->createUrl(Url::to('login')));
				} 
				*/
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
	
	
	public function actionMoveAllFilesToDefaultFtpServerX2s() {
		set_time_limit(9999);
		$model = new FtpFiles();
		
		pre(FtpFiles::find()->where("ftpServer != 'mysql9.nawww.ru'")->asArray()->all());
		
		$_POST['selection'] = ArrayHelper::getColumn(FtpFiles::find()->where("ftpServer != 'mysql9.nawww.ru'")->all(), 'id');
		
		$_POST['moveFtp'] = 'mysql9.nawww.ru';
		
		$_POST['move'] = 1;
		
		foreach ($_POST['selection'] as $id) {
			if ($file = FtpFiles::findOne($id)) {
				if ($_POST['moveFtp'] != $file->ftpServer) {
					$tmpPath = sprintf("%s/ftp_files_tmp/%s", $_SERVER['DOCUMENT_ROOT'], $file->name);
					$remotePath = sprintf('/%s/%s/', $file->category, $file->sha1);

					if (UploadedFileCustom::getFile($file->ftpServer, $tmpPath, $remotePath.$file->name)) {
						if (UploadedFileCustom::putFile($_POST['moveFtp'], $tmpPath, $remotePath, $file->name)) {
							UploadedFileCustom::delFile($file->ftpServer, $remotePath.$file->name);
							
							$file->ftpServer = $_POST['moveFtp'];
							$file->save();								
						}
						
						unlink($tmpPath);
					}
				}
			}
		}
		
		die(sprintf("Осталось перенсти %s", FtpFiles::find()->where("ftpServer != 'mysql9.nawww.ru'")->count()));		
	}
	
	public function actionDocs() {
		$model = new FtpFiles();

		if (isset($_POST['selection']) && count($_POST['selection'])>0) {
			if (isset($_POST['delete'])) {
				foreach ($_POST['selection'] as $selection) {
					$file = FtpFiles::findOne($selection);
					$remotePath = sprintf('/%s/%s/%s', $file->category, $file->sha1, $file->name);
					if (UploadedFileCustom::delFile($doc->ftpServer, $remotePath)) {
						$file->delete();
					}
				}
			} elseif (isset($_POST['check'])) {

			} elseif (isset($_POST['move']) && isset($_POST['moveFtp'])) {
				foreach ($_POST['selection'] as $id) {
					if ($file = FtpFiles::findOne($id)) {
						if ($_POST['moveFtp'] != $file->ftpServer) {
							$tmpPath = sprintf("%s/ftp_files_tmp/%s", $_SERVER['DOCUMENT_ROOT'], $file->name);
							$remotePath = sprintf('/%s/%s/', $file->category, $file->sha1);

							if (UploadedFileCustom::getFile($file->ftpServer, $tmpPath, $remotePath.$file->name)) {

								if (UploadedFileCustom::putFile($_POST['moveFtp'], $tmpPath, $remotePath, $file->name)) {
									UploadedFileCustom::delFile($file->ftpServer, $remotePath.$file->name);
									
									$file->ftpServer = $_POST['moveFtp'];
									$file->save();								
								}
								
								unlink($tmpPath);
							}
						}
					}
				}
			}
		}
		
		$orderBy = 'id'; 
		$order = SORT_DESC; 
		if (isset($_GET['sort'])) {
			if (substr($_GET['sort'], 0, 1) == '-') {
				$order = SORT_DESC;
				$orderBy = substr($_GET['sort'], 1, strlen($_GET['sort']));
			} else {
				$order = SORT_ASC;
				$orderBy = $_GET['sort'];
			}
		}
		
        $query = FtpFiles::find()->orderBy([$orderBy => $order]); 
		
		$users[0] = 'Все пользователи';
		foreach (User::find()->where("id NOT IN (519)")->all() as $user) {
			$users[$user->id] = $user->getFullName();
		}
		$companies = array_merge([0 => 'Все компании'], ArrayHelper::map(Company::find()->asArray()->all(), 'id', 'nameShort'));
		
		$startdate = date('m/d/Y', mktime(0, 0, 0, 1, 1, 2015));
		$stopdate = date('m/d/Y');
		
		$ftpServers[0] = 'Все сервера';
		$ftpServersList = [];
		foreach (Yii::$app->params['ftp-servers'] as $ftpServerUrl=>$ftpServer) {
			$ftpServers[$ftpServerUrl] = $ftpServerUrl;
			
			$connId = ftp_connect($ftpServer['host']);
			$ftpServersList[] = [
				'name' => $ftpServer['host'],
				'accessibility' => ftp_login($connId, $ftpServer['user'], $ftpServer['password'])
			];
		}
        
        if(isset($_GET['FtpFiles'])) {
			if (isset($_GET['FtpFiles']['name']) && $_GET['FtpFiles']['name']) $query->andWhere(['like', 'name', $_GET['FtpFiles']['name']]);			
			if (isset($_GET['companyId']) && $_GET['companyId']) $query->andWhere(['companyId' => $_GET['companyId']]);
			if (isset($_GET['userId']) && $_GET['userId']) $query->andWhere(['userId' => $_GET['userId']]);
			
			if (isset($_GET['FtpFiles']['dateAdd']) && strlen($_GET['FtpFiles']['dateAdd']) == 23) { 			
				$dateRang = explode(' - ', $_GET['FtpFiles']['dateAdd']);

				$dateRang[0] = explode('/', $dateRang[0]);
				$dateRang[1] = explode('/', $dateRang[1]);
		
				$startDate = date('Y-m-d H:i:s', mktime(0, 0, 0, intval($dateRang[0][0]), intval($dateRang[0][1]), intval($dateRang[0][2])));
				$stopDate = date('Y-m-d H:i:s', mktime(23, 59, 59, intval($dateRang[1][0]), intval($dateRang[1][1]), intval($dateRang[1][2])));
				
				$aWhereDate = [];
				$aWhereDate[] = sprintf("dateAdd > '%s'", $startDate);
				$aWhereDate[] = sprintf("dateAdd < '%s'", $stopDate);

				$query->andWhere(join(' AND ', $aWhereDate));							
			}
			
			if (isset($_GET['ftpServer']) && $_GET['ftpServer']) $query->andWhere(['ftpServer' => $_GET['ftpServer']]);
        }
        
		$dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
		
		return $this->render('docs-list', [
			'model' => $model,
			'dataProvider' => $dataProvider,
			'users' => $users,
			'companies' => $companies,
			'categories' => FtpFiles::getCategories(),
			'startdate' => $startdate,
			'stopdate' => $stopdate,
			'ftpServers' => $ftpServers,
			'ftpServersList' => $ftpServersList
		]);		
	}			
				 
	public function actionList()
	{	
        $model=new Company();
        $query=Company::find()->orderBy(["id" => SORT_DESC ])->where(['forDelete'=>0])->with('address','users');
                
        $badInn=ArrayHelper::map(Company::compareINN(), 'id', 'id');

        $directors=ArrayHelper::map(User::find()->where(['isDirector'=>1])->All(), 'id', 'name');
        
        $modelOkpd=null;
        if(isset($_GET['moreProductsOffer'])){
            $modelOkpd=Okpd::findOne($_GET['moreProductsOffer']);
        }

        if(isset($_GET['Company'])){
            $params=$_GET['Company'];
            $params['addRate']=$_GET['addRate'];
            $params['addRequist']=$_GET['addRequist'];
            $params['active']=$_GET['active'];
            
            if($params['director']){
                $uid=ArrayHelper::map(User::find()->andWhere(['like', 'name', $params['director']])->orWhere(['like', 'lastName', $params['director']])->orWhere(['like', 'patronymic', $params['director']])->All(), 'companyId', 'companyId');
                $query->andWhere(['id'=>$uid]);
                $model->director=$params['director'];
            }
            unset($params['director']);
            foreach($params as $key=>$el) {
                $query->andWhere(['like', $key, $el]);
                $model->$key=$el;
            }
            
            if($modelOkpd->name){
                if($modelDivision=Division::find()->select(['id'])->where(['like','name',$modelOkpd->name])->all()){
                    $ids=[];
                    foreach($modelDivision as $el)    $ids[]='#'.$el->id;
                    $query->andWhere(['or like', 'productsOffer', $ids]);
                }
                else    $query->andWhere(['id'=>-1]);
            }
        } 
        
        if($params=Yii::$app->request->get('filter')) {
            if($params['option_1']==1) {
                $query->andWhere(['>=','dateLast',$params['date_1_start']]);
                $query->andWhere(['<=','dateLast',$params['date_1_stop']]);
            }
            if($params['option_2']==1) {
               // $tenderList=Tenders::find()->select(Tenders::tableName().'.id')
               // ->where(['>=','dateCreate',$params['date_2_start']])
               // ->andWhere(['<=','dateCreate',date('Y-m-d',strtotime($params['date_2_stop'].' + 2 day'))]);
                
                if($params['option_4']==1) {
                    $uids=ArrayHelper::map(User::find()->select('id')->where(['companyId'=>$params['company_opt_4']])->All(), 'id', 'id');
                    $rateList=TenderLots::find()->select('tenderId')->where(['winId'=>$uids])->All();
                    //var_dump(count($rateList));die;
                //    $uids=ArrayHelper::map(User::find()->select('id')->where(['companyId'=>$params['company_opt_4']])->All(), 'id', 'id');
                    //$tenderList->joinWith('lots')->andWhere([TenderLots::tableName().'.winId'=>$uids]);
                }  
                
                //$tenderList=$tenderList->All();
                $tenderList=ArrayHelper::map($rateList, 'tenderId', 'tenderId');

                //$query->andWhere([TenderBackend::tableName().'.id'=>$tenderList]);
                //$query->andWhere(['>=','dateUpdate',$params['date_2_start']]);
                //$query->andWhere(['<=','dateUpdate',$params['date_2_stop']]);
            }
            if($params['option_3']==1){
                $query->andWhere(['isTransporter'=>1]);
            }
            if($params['option_4']==1){
                //$uids=ArrayHelper::map(User::find()->select('id')->where(['companyId'=>$params['company_opt_4']])->All(), 'id', 'id');
                //$rateList=TenderLots::find()->select('tenderId')->where(['winId'=>$uids])->All();
                //$tenderList=ArrayHelper::map($rateList, 'tenderId', 'tenderId');
                //$query->andWhere([TenderBackend::tableName().'.id'=>$tenderList]);
            }
			
			if($params['workingWithoutVAT']==1){
                $query->andWhere(['workingWithoutVAT'=>1]);
            }
			
			if($params['workingWithVAT']==1){
                $query->andWhere(['workingWithVAT'=>1]);
            }
			
			if (isset($params['groups']) && is_array($params['groups']) && count($params['groups']) > 0) {
				$companyGroupsId = ArrayHelper::getColumn(GroupCompanyRelations::find()->select('companyId')->where(sprintf("groupId in (%s)", join(',', $params['groups'])))->asArray()->all(), 'companyId');
				
				if (count($companyGroupsId) > 0) {
					$query->andWhere(sprintf('id in (%s)', join(',', $companyGroupsId)));
				}
			}
			
			if (isset($params['okpds']) && is_array($params['okpds']) && count($params['okpds']) > 0) {
				$sql = sprintf("
					SELECT 
						DISTINCT(c.companyId) as companyId
					FROM 
						b2b_ClassificationsOkpdGraphs as g,
						b2b_CompanyDivision as c
					WHERE 
						c.divisionId = g.childId AND
						g.parentId in (%s)
				", join(',', $params['okpds']));
				
				$companyOkpdsId = ArrayHelper::getColumn(Yii::$app->db->createCommand($sql)->queryAll(), 'companyId');
				
				if (count($companyOkpdsId) > 0) {
					$query->andWhere(sprintf('id in (%s)', join(',', $companyOkpdsId)));
				}
			}
			
			if (isset($params['companies']) && is_array($params['companies']) && count($params['companies']) > 0) {
				$sql = sprintf("
					SELECT 
						DISTINCT(u.companyId) as companyId
					FROM 
						b2b_Tenders as t,
						b2b_Rates as r,
						b2b_User as u,
						b2b_Company as c
					WHERE 
						t.companyId in (%s) AND
						t.id = r.tenderId AND
						r.userId = u.id AND
						u.companyId = c.id
						
				", join(',', $params['companies']));
				
				$companiesId = ArrayHelper::getColumn(Yii::$app->db->createCommand($sql)->queryAll(), 'companyId');
				
				if (count($companiesId) > 0) {
					$query->andWhere(sprintf('id in (%s)', join(',', $companiesId)));
				}
			}
        }
        
		$dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
		
		$companySelect = [];
		
		$sql = sprintf("
			SELECT 
				c.*
			FROM 
				b2b_Company as c
			WHERE 
				(
					SELECT COUNT(id) FROM b2b_Tenders where companyId = c.id
				) > 0
				
		", join(',', $params['okpds']));
		
		$companySelect = ArrayHelper::map(Yii::$app->db->createCommand($sql)->queryAll(), 'id', 'name');

        return $this->render('list', [
			'groups' => Group::find()->all(),
            'dataProvider' => $dataProvider,
            'directors' => $directors,
            'model' => $model,
            'badInn' => $badInn,
            'modelOkpd' => $modelOkpd,
			'okpdList' => Okpd::getTreeList(),
			'companySelect' => $companySelect
        ]);
	}
    
    public function actionUnactive()
	{	
        $model=new Company();
        $query=Company::find()->orderBy(["id" => SORT_DESC ])->andWhere(['active'=>0])->andWhere(['forDelete'=>0]);
        
        $directors=ArrayHelper::map(User::find()->where(['isDirector'=>1])->All(), 'id', 'name');
        
        if(isset($_GET['Company'])){
            $params=$_GET['Company'];
            $params['addRate']=$_GET['addRate'];

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

        return $this->render('unactive', [
            'dataProvider' => $dataProvider,
            'directors'=>$directors,
            'model' => $model,
        ]);
	}

	public function actionView($id='')
	{
		if($id && is_numeric($id)){
			if($model=Company::companyById($id)){
                $directors=ArrayHelper::map(User::find()->where(['isDirector'=>1])->All(), 'id', 'userName');  
                $addr=ArrayHelper::map($model->address, 'id', 'addressName');   
                
                $rating=CompanyComment::totalRatingById($id);      
                $badInn=Company::compareINN($model->INN);  
                
                $query=ActionLogs::oneByModelIdProvider('Company',$id);
                $dataProvider = new ActiveDataProvider([
                    'query' =>$query,
                ]);
                
                $groupList=ArrayHelper::map( Group::itemList(), 'id', 'title');
                
                $companyGroupProvider = new ActiveDataProvider([
                    'query' =>GroupCompanyRelations::groupListCompanyProvider($id),
                ]);
                
                if($model->forDelete)   Yii::$app->session->setFlash('danger', 'Компания находится на удалении');
				
				$info = $model->getInfo();
                $companyUsers = $model->getUsersCompany();
                

    			return $this->render('view', [
					'info' => $model->getInfo(),
    				'model' => $model,
                    'directors'=>$directors,
                    'addr'=>$addr,
                    'rating'=>$rating,
                    'badInn'=>$badInn,
                    'dataProvider' => $dataProvider,
                    'groupList'=>$groupList,
                    'companyGroupProvider'=>$companyGroupProvider,
                    'companyUsers' => $companyUsers
    			]);
            }
            else $this->redirect('/company/list');
		}
	}

	public function actionDelete($id='')
	{
	   if($id && is_numeric($id)){
			$company = Company::findOne($id);			
			$company->scenario = 'reguser';
			$company->forDelete = 1;

            if($company->save()) Yii::$app->session->setFlash('success', 'Компания помечена как удаленная');
            else Yii::$app->session->setFlash('danger', 'Не удалось удалить компанию');
            $this->redirect('/company/view?id='.$company->id);
	   }
	}
    
    public function actionGroupAdd($companyId=null){
        if(($post=Yii::$app->request->post('group_list')) && $companyId){
            $fields=[
                'offer'=>Yii::$app->request->post('offer'),
                'demand'=>Yii::$app->request->post('demand'),
            ];
            
            foreach($post as $el){
                GroupCompanyRelations::addItem($el,$companyId,['fields'=>$fields]);
            }
            Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
        }
        return $this->redirect(['/company/view/', 'id' => $companyId]);
    }
    
    public function actionGroupDelAll($companyId=null){
        if(($selected=Yii::$app->request->post('selection')) && $companyId){  
            $post=Yii::$app->request->post();

            if(isset($post['save'])){
                foreach($selected as $el){
                    if($model=GroupCompanyRelations::oneById($el,$companyId)){
                        $model->offer=isset($post['offer'][$el])?$post['offer'][$el]:0;
                        $model->demand=isset($post['demand'][$el])?$post['demand'][$el]:0;
                        $model->save();
                    }
                }
                Yii::$app->session->setFlash('success', 'Данные успешно сохранены'); 
            }
            else if(isset($post['remove'])){
                foreach($selected as $el){
                    GroupCompanyRelations::delItem($el,$companyId);
                }
                Yii::$app->session->setFlash('success', 'Компании удалены из списка');    
            }
        }
        else{
            Yii::$app->session->setFlash('danger', 'Выберите один или несколько пунктов');
        }
        return $this->redirect(['/company/view/', 'id' => $companyId]);
    }
    
    public function actionGroupDel($groupId=null,$companyId=null){
        if($groupId && $companyId){ 
            if(GroupCompanyRelations::delItem($groupId,$companyId))  Yii::$app->session->setFlash('success', 'Компания удалена из списка');
            else    Yii::$app->session->setFlash('danger', 'Не удалось удалить компанию');
        }
        return $this->redirect(['/company/view/', 'id' => $companyId]);
    }
    
    public function actionComments($id=null)
	{
	   if($id && is_numeric($id)){
			$model = CompanyComment::itemListByCompanyId($id);	
            $rating=CompanyComment::totalRatingById($id); 		
            return $this->render('comments', [
                'model' => $model,
                'rating'=>$rating,
                'id'=>$id
            ]);
	   }
	}
		
	public function actionUpdate($id='')
	{
		echo $this->actionEdit($id);
	}
	
	public function actionCreate($id='')
	{
		echo $this->actionEdit($id);
	}
	
	public function actionEdit($id='')
	{
        if($id && is_numeric($id)){
	       $model=Company::companyById($id);
        }
  		else{
            $model=new Company();
  		}
        
        $address=ArrayHelper::map(CompanyAddress::addressByCompanyId($id), 'id', 'addressName');

        if(isset($_POST["Company"])){
			$oldActive = $model->active;
            $model->attributes=$_POST["Company"];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    

					if ($_POST['Company']['active'] != $oldActive && $oldActive===0) {
						foreach ($model->users as $user) {
							
							/*
							$mail = new Delivery();			
							
							$mail->subject = 'Ваш личный помощник на Agro2b.com';			
							$title = Yii::t('mail', 'Здравствуйте, {name}!', ['name' => $user->getFullName(false)]);
							$mail->text = Yii::$app->controller->renderPartial('@frontend/mail/default/activation', [
								'title'=> $title,
								'message' => Yii::$app->controller->renderPartial('@common/mail/newCompany')
							]);
							
							$mail->status = 0;
							$mail->prioritet = 0;	
							$mail->uids = $user->id;
							$mail->uidFrom	= Messages::SENDER_ID;

							$mail->save();
							*/
							
							Newdelivery::addMessageToDelivery(
							[
								'subject' => 'Ваш личный помощник на Agro2b.com',
								'body' => Yii::$app->controller->renderPartial('@frontend/mail/default/activation', [
									'title'=> Yii::t('mail', 'Здравствуйте, {name}!', ['name' => $user->getFullName(false)]),
									'message' => Yii::$app->controller->renderPartial('@common/mail/newCompany')
								]),
								'priority' => 0,
								'projectId' => 2,
								'emailTo' => [User::find()->where(['id' => $user->id])->one()->userName],
								'emailFrom' => User::find()->where(['id' => Yii::$app->params['adminId']])->one()->userName,
							]);
						}
					}
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/company/'.$model->id);
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
            //$model=UserFormEdit::update($id,$attr);
            //if(!$model->getErrors())    return $this->redirect('/users/'.$model->id);
        }

		return $this->render('update', [
			'model' => $model,
            'address'=>$address,
		]);
	}
    
    
/**
* Addres company
*/
    public function actionAddress($id=null){
        if($id && is_numeric($id)){
			if($model=CompanyAddress::addressById($id)){
                return $this->render('viewAddress', [
		          	'model' => $model,
                ]);
            }
        }  
    }
    
    public function actionAddressupdate($id=null)
	{
		echo $this->actionAddressedit($id);
	}
	public function actionAddresscreate($id=null)
	{
		echo $this->actionAddressedit($id);
	}
    
    public function actionAddressedit($id=null)
	{
		if($id && is_numeric($id)){
            $model=CompanyAddress::addressById($id);
        }
  		else{
            $model=new CompanyAddress();
            $model->companyId=isset($_GET['cid'])?$_GET['cid']:null;
  		}
        
        if(isset($_POST["CompanyAddress"])){
            //var_dump($_POST['CompanyAddress']);die;
            $model->attributes=$_POST["CompanyAddress"];
            $model->dateEdit=date('Y-m-d H:i:s',time());
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/company/address/'.$model->id);
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
            //$model=UserFormEdit::update($id,$attr);
            //if(!$model->getErrors())    return $this->redirect('/users/'.$model->id);
        }
        
        $companys=ArrayHelper::map(Company::find()->where(['forDelete'=>0])->All(), 'id', 'nameShort');
        return $this->render('_formAddress', [
			'model' => $model,
            'companys'=>$companys,
		]);
	}
    
    public function actionAddressdelete($id='')
	{
	   if($id && is_numeric($id)){
            if(CompanyAddress::deleteall(['id'=>$id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
            $this->redirect('/company/list');
	   }
	}
    
    
    /**
     * Phones
     */
     public function actionPhone($id=null){
        if($id && is_numeric($id)){
			if($model=CompanyPhone::phoneById($id)){
                $types=CompanyPhone::$phoneTypes;
                return $this->render('viewPhone', [
		          	'model' => $model,
                    'types'=>$types
                ]);
            }
        }  
    }
    
    public function actionPhoneupdate($id=null)
	{
		echo $this->actionPhoneedit($id);
	}
	public function actionPhonecreate($id=null)
	{
		echo $this->actionPhoneedit($id);
	}
    
    public function actionPhoneedit($id=null)
	{
		if($id && is_numeric($id)){
            $model=CompanyPhone::phoneById($id);
        }
  		else{
            $model=new CompanyPhone();
            $model->companyId=isset($_GET['cid'])?$_GET['cid']:null;
  		}
        
        if(isset($_POST["CompanyPhone"])){
            //var_dump($_POST['CompanyAddress']);die;
            $model->attributes=$_POST["CompanyPhone"];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
					return $this->redirect('/company/phone/'.$model->id);
                }
                else {
					Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
				}
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
            //$model=UserFormEdit::update($id,$attr);
            //if(!$model->getErrors())    return $this->redirect('/users/'.$model->id);
        }
        
        $types=CompanyPhone::$phoneTypes;
        $companys=ArrayHelper::map(Company::find()->where(['forDelete'=>0])->All(), 'id', 'nameShort');
        return $this->render('_formPhone', [
			'model' => $model,
            'types'=>$types,
            'companys'=>$companys,
		]);
	}
    
    public function actionPhonedelete($id='')
	{
	   if($id && is_numeric($id)){
            if(CompanyPhone::deleteall(['id'=>$id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
            $this->redirect('/company/list');
	   }
	}
    
    public function actionDocument($id=null,$cid=null){
        if($id && is_numeric($id)){
            $model=Company::companyById($cid);
			if($modelDoc=FtpFiles::oneById($id)){
			     $patternList=ArrayHelper::map(DocPatterns::find()->all(), 'id', 'about');
                 
                return $this->render('docView', [
		          	'model' => $model,
                    'modelDoc' => $modelDoc,
                    'patternList'=>$patternList
                ]);
            }
        }  
    }
    
    public function actionDocumentview($cid=null){    
        $model=Company::companyById($cid);
		$patternList1 = DocPatterns::find()->where(['group' => 1])->orderBy('sortOrder')->all();
		$patternList2 = DocPatterns::find()->where(['group' => 2])->all();
		$list = FtpFiles::findAll([
			'companyId' => $cid,
			'category' => FtpFiles::CATEGORY_DOCS
		]);
		
		$sql = sprintf("
			SELECT 
				DISTINCT(p.patternId) as id
			FROM 
				b2b_DocPatternGroupCross as p,
				b2b_GroupCompanyRelations as c
			WHERE
				c.companyId = %s AND
				c.groupId = p.groupId					
		", $model->id);
		
		$patternIds = ArrayHelper::getColumn(Yii::$app->db->createCommand($sql)->queryAll(), 'id');
		if (count($patternIds) == 0) $patternIds[] = 0;
		
		$categoriesTree = [];

		foreach (DocPatternCategories::find()->where(['parentId' => 0])->orderBy('sortOrder')->asArray()->all() as $cat) {
			$categoriesTree[$cat['id']] = $cat;
			$categoriesTree[$cat['id']]['children'] = [];
            
            $pat = DocPatterns::find()->orderBy('sortOrder')->where(sprintf("categoryId = %s", $cat['id']));
            if($patternIds[0]!=0)   $pat->andWhere(['IN','id',$patternIds]);
			$categoriesTree[$cat['id']]['patterns'] = $pat->asArray()->all();
		}

		foreach (DocPatternCategories::find()->where('parentId != 0')->orderBy('sortOrder')->asArray()->all() as $cat) {
			foreach ($categoriesTree as $catId=>$categoryTree) {
				if ($catId == $cat['parentId']) {
					$categoriesTree[$catId]['children'][$cat['id']] = $cat;			
                    
                    $pat = DocPatterns::find()->orderBy('sortOrder')->where(sprintf("categoryId = %s", $cat['id']));
                    if($patternIds[0]!=0)   $pat->andWhere(['IN','id',$patternIds]);
            				
					$categoriesTree[$catId]['children'][$cat['id']]['patterns'] = $pat->asArray()->all();
				}
			}
		}
		
		foreach($list as $el) $docs[$el->patternId][]=$el;
		

		
		return $this->render('docs', [
			'model' => $model,
			'categoriesTree'=>$categoriesTree,
			'patternIds'=>$patternIds,
			'docs'=>$docs,
		]);
	}  
    
    public function actionDocumentviewText($cid=null) {
        $model=Company::companyById($cid);
		$patternList1 = DocPatterns::find()->where(['group' => 1])->orderBy('sortOrder')->all();
		$patternList2 = DocPatterns::find()->where(['group' => 2])->all();
		$list = FtpFiles::findAll([
			'companyId' => $cid,
			'category' => FtpFiles::CATEGORY_DOCS
		]);
		
		$sql = sprintf("
			SELECT 
				DISTINCT(p.patternId) as id
			FROM 
				b2b_DocPatternGroupCross as p,
				b2b_GroupCompanyRelations as c
			WHERE
				c.companyId = %s AND
				c.groupId = p.groupId					
		", Yii::$app->user->identity->companyId);
		
		$patternIds = ArrayHelper::getColumn(Yii::$app->db->createCommand($sql)->queryAll(), 'id');
		
		foreach($list as $el) {
			if (in_array($el->patternId, $patternIds)) {
				$docs[$el->patternId][] = $el;
			}
		}
		
		
		
		return $this->render('docs-text', [
			'model' => $model,
			'patternList1'=>$patternList1,
			'patternList2'=>$patternList2,
			'docs'=>$docs,
		]);
	}

    public function actionDocumentdel($cid=null,$id=null) {
        if($id && is_numeric($id)){
            if(FtpFiles::deleteall(['id'=>$id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
            $this->redirect('/company/documentview?cid='.$cid);
	    }
    }
    
    public function actionDocumentedit($id=null,$cid=null)
	{
	   $model=Company::companyById($cid);
		if($id && is_numeric($id)){
            $modelDoc=FtpFiles::oneById($id);
        }

        if(isset($_POST["FtpFiles"])){
            //var_dump($_POST['CompanyAddress']);die;
            $modelDoc->attributes=$_POST["FtpFiles"];

            if($modelDoc->validate()){
                if($modelDoc->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/company/documentview/?cid='.$cid);
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }

        }
        
        $patternList=ArrayHelper::map(DocPatterns::find()->all(), 'id', 'about');
        $userList=ArrayHelper::map(User::find()->All(), 'id', 'name');
        return $this->render('docEdit', [
			'model' => $model,
            'modelDoc' => $modelDoc,
            'patternList'=>$patternList,
            'userList'=>$userList
		]);
	}
        
    public function actionDocumentadd($cid=null,$patternId=null){    
            $model=Company::companyById($cid);
            $patternList=ArrayHelper::map(DocPatterns::find()->all(), 'id', 'about');

            $modelDoc = new FtpFiles();
            $modelDoc->scenario = FtpFiles::CATEGORY_DOCS;
            $modelDoc->patternId=$patternId;

            return $this->render('docsAdd', [
    			'model' => $model,
                'patternList'=>$patternList,
                'modelDoc'=>$modelDoc,
    		]);
        }  
        
        public function actionDocumentupload($cid=null,$patternId=null){    
            $model = new FtpFiles();
            $model->scenario = FtpFiles::CATEGORY_DOCS;
            
            if (Yii::$app->request->isPost) {
                if($model->file = UploadedFileCustom::getInstance($model, 'file')) {
						if ($model->validate()) {                
							$model->file->server = $model->ftpServersDefault[FtpFiles::CATEGORY_DOCS];
							$model->title = $_POST['FtpFiles']['title'];
							$model->category = FtpFiles::CATEGORY_DOCS;

                            if($model->file->tempName) {
    							$model->sha1 = sha1_file($model->file->tempName);
								$path = sprintf('/%s/%s', FtpFiles::CATEGORY_DOCS, $model->sha1);
								
								if ($model->file->saveAs($path, $model->file->name, false)) {
									// если стоит модуль юзаем его, иначе берем инфу из сопроводительной инфы
									if (function_exists('finfo_open')) {
										$finfo = finfo_open(FILEINFO_MIME);
										if ($finfo) {
											$model->mime = finfo_file($finfo, $model->file->tempName);
										}
									} else {
										$model->mime = $model->file->type;
									}
									
									$model->name = $model->file->name;
									$model->size = filesize($model->file->tempName);
									$model->userId = Yii::$app->user->getId();
									$model->companyId = $cid;
									$model->patternId = $patternId;
									$model->ftpServer = $model->file->server;
									
									if ($model->save()) {
										Yii::$app->session->setFlash('success', Yii::t('controller', 'Файл {file} добавлен' , ['file' => $model->name]));
									}
								} else {
									$model->addError('file', join(', ', $model->file->errors));
								}
                            }
                            else if(!$model->file->size) Yii::$app->session->setFlash('danger', Yii::t('controller', 'Не удалось добавить файл {file}. Превышен допустимый размер файла.' , ['file' => $model->name]));
						}
					}
     
            }
            $this->redirect('/company/documentview?cid='.$cid);
        }    
        
        public function actionExport(){
            if (Yii::$app->request->isPost) {
                $title='Контакты';
                
                $model=Company::find()->with('users','address','phones')->where(['forDelete'=>0]);
                if($attr=Yii::$app->request->get()){
                    foreach($attr as $key=>$el){
                        if($el)     $model->andWhere(['like',$key,$el]);
                    }
                }
                
                $row=1;
                $pp=0;
                
                $rows[$row][]='№ п/п';
                $rows[$row][]='Дата';
                $rows[$row][]='id';
                $rows[$row][]='Название';
                $rows[$row][]='ИНН';
                $rows[$row][]='Адрес';
                $rows[$row][]='Контактное лицо';
                $rows[$row][]='Телефон';
                $rows[$row][]='E-Mail';
                $rows[$row][]='Комментарий';
                $rows[$row][]='Дата следующего контакта';
    
                foreach($model->All() as $key=>$el){
                    $row++;
                    $rows[$row]=[];
                    
                    $row++;
                    $pp++;
                    $i=0;
                    
                    $rows[$row][]=$pp;
                    $rows[$row][]='';
                    $rows[$row][]=$el->id;
                    $rows[$row][]=$el->nameShort;
                    $rows[$row][]=$el->INN;
                    $rows[$row][]=$el->address[$i]->addressName?($el->address[$i]->addressName.' ('.$el->address[$i]->address.')'):'';
                    $rows[$row][]=$el->users[$i]->name.' '.$el->users[$i]->patronymic;
                    $rows[$row][]=$el->users[$i]->phone;
                    $rows[$row][]=$el->users[$i]->userName;
                    $rows[$row][]='';
                    $rows[$row][]='';
                    
                    if($el->address){
                        foreach($el->address as $addr){
                            $row++;
                            $i++;
                            
                            $rows[$row][]='';
                            $rows[$row][]='';
                            $rows[$row][]='';
                            $rows[$row][]='';
                            $rows[$row][]='';
                            $rows[$row][]=$el->address[$i]->addressName?($el->address[$i]->addressName.' ('.$el->address[$i]->address.')'):'';
                            $rows[$row][]=$el->users[$i]?($el->users[$i]->name.' '.$el->users[$i]->patronymic):'';
                            $rows[$row][]=$el->users[$i]?$el->users[$i]->phone:'';
                            $rows[$row][]=$el->users[$i]?$el->users[$i]->userName:''; 
                            $rows[$row][]='';
                            $rows[$row][]=''; 
                            
                            unset($el->users[$i]);
                        } 
                    }
                    
                    if($el->users){
                        foreach($el->users as $user){
                            $row++;

                            $rows[$row][]='';
                            $rows[$row][]='';
                            $rows[$row][]='';
                            $rows[$row][]='';
                            $rows[$row][]='';
                            $rows[$row][]='';
                            $rows[$row][]=$el->users[$i]?($el->users[$i]->name.' '.$el->users[$i]->patronymic):'';
                            $rows[$row][]=$el->users[$i]?$el->users[$i]->phone:'';
                            $rows[$row][]=$el->users[$i]?$el->users[$i]->userName:'';
                            $rows[$row][]='';
                            $rows[$row][]='';   
                        } 
                    }
                }

                require_once Yii::getAlias('@backend').'/modules/CRM/controllers/PHPExcel/PHPExcel/IOFactory.php';
                require_once Yii::getAlias('@backend').'/modules/CRM/controllers/PHPExcel/PHPExcel.php';
                
                $xls = new \PHPExcel();
                $xls->setActiveSheetIndex(0);
                $sheet = $xls->getActiveSheet();
                
                $sheet->setTitle($title);
                $filename = sprintf("%s_%s.xls", $title, date('Y.m.d'));

                //$sheet->fromArray($rows);
                //$columns_count = \PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
                $keyR=1;
                foreach($rows as $row){
                    foreach($row as $keyC=>$el){
                        $sheet->setCellValueByColumnAndRow($keyC, $keyR, $el);
                        $adjustedColumn = \PHPExcel_Cell::stringFromColumnIndex($keyC);
                        $sheet->getColumnDimension($adjustedColumn)->setAutoSize(TRUE);
                    }
                    $keyR++;
                }
                
                $style_hprice = array(
                	//выравнивание
                	'alignment' => array(
                		'horizontal' => \PHPExcel_STYLE_ALIGNMENT::HORIZONTAL_CENTER,
                	),
                //заполнение цветом
                	'fill' => array(
                		'type' => \PHPExcel_STYLE_FILL::FILL_SOLID,
                		'color'=>array(
                			'rgb' => 'FFFF33'
                		)
                	),
                );
                $sheet->getStyle('A1:K1')->applyFromArray($style_hprice);
                
                $file=Yii::getAlias('@backend').'/'.$filename;
                
                $objWriter = \PHPExcel_IOFactory::createWriter($xls, 'Excel5');
                $objWriter->save($file);
                
                header('Content-Description: File Transfer');
                header('Content-Type: application/xls');
                header('Content-Disposition: attachment; filename=' . $filename);
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                readfile($file);
                unlink($file);
                exit;   
            }
        }         
        
        public function actionOkpdlist($q = null, $id = null) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $out = ['results' => ['id' => '', 'name' => '','code'=>'']];

            if (!is_null($q)) {
                $model=Okpd::find()->select(['id','name','code'])->where(['like','code',$q])->orWhere(['like','name',$q])->All();
                $out['results'] = array_values($model);
            }

            return $out;
        }     
        
        public function actionAddBillingBlackList($cid=null){
            if (Yii::$app->request->isPost) {
                if(TarifCompanyBlack::addCompany($cid)) Yii::$app->session->setFlash('success', 'Компания удалена из списка отчетов');
                else    Yii::$app->session->setFlash('danger', 'Не удалось выполнить действие');
            }
            $this->redirect('/company/view?id='.$cid);
        }
        
        public function actionDelBillingBlackList($cid=null){
            if (Yii::$app->request->isPost) {
                if(TarifCompanyBlack::delCompany($cid)) Yii::$app->session->setFlash('success', 'Компания добавленна из списка отчетов');
                else    Yii::$app->session->setFlash('danger', 'Не удалось выполнить действие');
            }
            $this->redirect('/company/view?id='.$cid);
        }
        
        public function actionRegisterExcel()
        {
            if(Yii::$app->request->post()){
                $file = UploadedFile::getInstanceByName('file');
                
                if($file)
                {
                    require_once Yii::getAlias('@backend').'/modules/CRM/controllers/PHPExcel/PHPExcel/IOFactory.php';
                    require_once Yii::getAlias('@backend').'/modules/CRM/controllers/PHPExcel/PHPExcel.php';
                    
                    $file->saveAs($file->name);
                    
                    $objPHPExcel = \PHPExcel_IOFactory::load($file->name);

                    unlink($file);
                    
                    foreach ($objPHPExcel->getWorksheetIterator() as $worksheet)
                    {
                        $highestRow = $worksheet->getHighestRow(); // например, 10
                        
                        $i = 0;
                        for ($row = 11; $row <= $highestRow; $row ++)
                        {
                            if($company = $worksheet->getCellByColumnAndRow(5, $row)->getValue())
                            {
                                $i ++ ;
                                $okpds = [];
                                
                                if(trim($worksheet->getCellByColumnAndRow(12, $row)->getValue()))   $okpds = array_merge($okpds, [7, 10, 15, 18]);   //пшеница
                                if(trim($worksheet->getCellByColumnAndRow(13, $row)->getValue()))   $okpds = array_merge($okpds, [166, 167]);   //семена подсолнечника
                                if(trim($worksheet->getCellByColumnAndRow(14, $row)->getValue()))   $okpds = array_merge($okpds, [50, 53]);   //Ячмень
                                if(trim($worksheet->getCellByColumnAndRow(15, $row)->getValue()))   $okpds = array_merge($okpds, [28, 33, 38, 43, 221]);   //Кукуруза
                                if(trim($worksheet->getCellByColumnAndRow(16, $row)->getValue()))   $okpds = array_merge($okpds, [106, 123, 124]);   //Семена гороха
                                if(trim($worksheet->getCellByColumnAndRow(17, $row)->getValue()))   $okpds = array_merge($okpds, [149, 150, 151]);   //Семена льна
                                if(trim($worksheet->getCellByColumnAndRow(18, $row)->getValue()))   $okpds = array_merge($okpds, [157, 158, 160, 161]);   //Семена рапса
                                if(trim($worksheet->getCellByColumnAndRow(19, $row)->getValue()))   $okpds = array_merge($okpds, [58, 61]);   //Рожь
                                if(trim($worksheet->getCellByColumnAndRow(20, $row)->getValue()))   $okpds = array_merge($okpds, [173]);   //Семена рыжика
                                if(trim($worksheet->getCellByColumnAndRow(21, $row)->getValue()))   $okpds = array_merge($okpds, [138, 139, 1832, 1900]);   //Соевый
                                if(trim($worksheet->getCellByColumnAndRow(22, $row)->getValue()))   $okpds = array_merge($okpds, [174]);   //Семена сафлора 
                                if(trim($worksheet->getCellByColumnAndRow(23, $row)->getValue()))   $okpds = array_merge($okpds, [1838, 1906]);   //Масло подсолнечное и его фракции нерафинированные 
                                if(trim($worksheet->getCellByColumnAndRow(24, $row)->getValue()))   $okpds = array_merge($okpds, [1842, 1910]);   //Масло рапсовое, сурепное, горчичное и их фракции нерафинированные 
                                if(trim($worksheet->getCellByColumnAndRow(25, $row)->getValue()))   $okpds = array_merge($okpds, [2360, 2361]);   //Масло кукурузное
                                
                                $data[$i] = [
                                    'company' => trim($company),
                                    'inn' => trim($worksheet->getCellByColumnAndRow(0, $row)->getValue()),
                                    'users' => [
                                        [
                                            'name' => trim($worksheet->getCellByColumnAndRow(3, $row)->getValue()),
                                            'phone' => trim($worksheet->getCellByColumnAndRow(8, $row)->getValue()),
                                            'email' => trim($worksheet->getCellByColumnAndRow(9, $row)->getValue()),
                                        ]
                                    ],
                                    'okpds' => $okpds
                                ];    
                            }
                            else if($user = $worksheet->getCellByColumnAndRow(3, $row)->getValue())
                            {
                                $data[$i]['users'][] = [
                                    'name' => trim($user),
                                    'phone' => trim($worksheet->getCellByColumnAndRow(8, $row)->getValue()),
                                    'email' => trim($worksheet->getCellByColumnAndRow(9, $row)->getValue()),
                                ];
                            }

                        }

                        RegistrationCustom::registrationFromExcel($data);
                    } 
                }          
                else Yii::$app->session->setFlash('error', 'Ошибка загрузки файла');     
            }
            
            return $this->render('register', [
    			
    		]);    
        }
}