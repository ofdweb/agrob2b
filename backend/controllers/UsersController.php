<?php
namespace backend\controllers;

use Yii;

use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\Company;
use common\models\DeliverySms;
use common\models\UserHistoryLog;
use common\models\TendersHistory;
use common\models\Messages;
use common\models\MessagesConfig;
use common\models\Tenders;
use common\models\User;
use common\models\TenderDefault;
use common\models\FtpFiles;
use common\models\UploadedFileCustom;
use common\models\Currency;
use common\models\Items;
use common\models\CompanyAddress;
use common\models\Invitations;
use common\models\TenderInvitationsDefault;
use common\models\UserCompany;

use frontend\models\SignupForm;

use backend\models\UserFormEdit;

/**
 * Site controller
 */
class UsersController extends Controller
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
    
	
	public function actionList($params='')
	{	
        $model=new UserFormEdit();
        $query=UserFormEdit::load()->with('companys');
        if(isset($_GET['UserFormEdit'])){
            if($_GET['UserFormEdit']['companyId']){
                $comp=ArrayHelper::map(Company::find()->where(['forDelete'=>0])->andWhere(['like', 'nameShort', $_GET['UserFormEdit']['companyId']])->All(), 'id', 'id');
                $query->andWhere(['companyId'=>$comp]);
                $model->companyId=$_GET['UserFormEdit']['companyId'];
            }
            unset($_GET['UserFormEdit']['companyId']);
            foreach($_GET['UserFormEdit'] as $key=>$el){
                if($el){
                    $query->andWhere(['like', $key, $el]);
                    $model->$key=$el;
                }
            }
        }
		
		$dataProvider = new ActiveDataProvider([
            'query' =>$query,
            'sort' => array(
                'attributes' => array(
                    'companyId' => array(
                        'asc' => array('companyId' => SORT_ASC),
                        'desc'=> array('companyId' => SORT_DESC)
                    ),
                    'id','name','userName'
                )
            ),
        ]);

        return $this->render('list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
	}
	
	public function actionView($id='')
	{
		
		if($id && is_numeric($id)){
			if($model=UserFormEdit::load($id)){
			     $userCompanyList = UserCompany::itemList($id);
			     return $this->render('view', [
    				'model' => $model,
                    'options' => Messages::getTypes(),
                    'config' => MessagesConfig::getConfig(),
                    'userCompanyList' => $userCompanyList
    			]);
			}
		}
        $this->redirect('/users/list');
	}
	
    public function actionMessageconfig($id=null){
        $model=UserFormEdit::load($id);
        
        $params = [];
        $options = Messages::getTypes($params);
        
        if (isset($_POST['save'])) {
			$config = (isset($_POST['config'])) ? $_POST['config'] : [];		
			MessagesConfig::setConfig($config, $id, ArrayHelper::getColumn($options, 'id'));

			Yii::$app->session->setFlash('success', Yii::t('controller', 'Настройки сохранены'));
		}
        
        return $this->render('messageConfig', [
            'model' => $model,
            'options' => Messages::getTypes(),
            'config' => MessagesConfig::getConfig($id),
	   ]);
    }
	
	public function actionDelete($id='')
	{
	   if($id && is_numeric($id)){
            UserFormEdit::delete($id);
            $this->redirect('/users/list');
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
	
	public function actionDefault($id) {
		if (!isset($_GET['a']) || !in_array($_GET['a'], ['buy', 'sell'])) {
			return $this->redirect('/users/'.$id);
		}
		
		$type = $_GET['a'];
		$user = User::findOne($id);
		
		if ($user->id) {
			if (TenderDefault::find()->where([
				'userId' => $id,
				'type' => $type
			])->count() > 0) {
				$model = TenderDefault::find()->where([
					'userId' => $id,
					'type' => $type
				])->one();
			} else {
				$model = new TenderDefault();
				$model->save();
			}
			
			$tab = 1;
			
			if (isset($_GET['tab'])) $tab = $_GET['tab'];
			
			if (isset($_POST['saveCompanyFile'])) {
				if (isset($_POST['companyFile'])) {
					$tenderFile = new TenderFilesDefault();
					$tenderFile->companyId = $user->companyId;
					$tenderFile->fileId = $_POST['companyFile'];
					$tenderFile->type = $type;
					$tenderFile->save();
					
					//return $this->redirect(Yii::$app->UrlManager->createUrl(sprintf('/personal/tender/edit-%s?id=%s&a=files', $tender->type, $tender->id)));
				}
				
				$tab = 3;
			}

			$file = new FtpFiles();
			$file->scenario = FtpFiles::CATEGORY_DOCS;
				
			if($file->file = UploadedFileCustom::getInstance($file, 'file')) {
				if ($file->validate()) {                
					$file->file->server = $file->ftpServersDefault[FtpFiles::CATEGORY_DOCS];

					$file->title = ' ';
					$file->category = FtpFiles::CATEGORY_DOCS;

					if($file->file->tempName) {
						$file->sha1 = sha1_file($file->file->tempName);
						$path = sprintf('/%s/%s', FtpFiles::CATEGORY_DOCS, $file->sha1);
						
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
							
							$file->name = $file->file->name;
							$file->size = filesize($file->file->tempName);
							$file->userId = $id;
							$file->companyId = $user->companyId;
							$file->patternId = $_POST['FtpFiles']['patternId'];
							$file->ftpServer = $file->file->server;
							
							if ($file->save()) {
								$tenderFile = new TenderFilesDefault();
								$tenderFile->companyId = $user->companyId;
								$tenderFile->fileId = $file->id;
								$tenderFile->type = $type;
								$tenderFile->save();
								
								Yii::$app->session->setFlash('info', Yii::t('controller', 'Файл {file} добавлен' , ['file' => $file->name]));
								//return $this->redirect(Yii::$app->UrlManager->createUrl(sprintf('/personal/tender/edit-sell?id=%s&a=files', $tender->id)));
							}
						} else {
							$file->addError('file', join(', ', $file->file->errors));
						}
					}
					else if(!$file->file->size) Yii::$app->session->setFlash('danger', Yii::t('controller', 'Не удалось добавить файл {file}. Превышен допустимый размер файла.' , ['file' => $file->name]));
				}
				
				$tab = 3;
			}

			if (isset($_GET['a']) && $_GET['a'] == 'delInvite') {
				if (isset($_GET['inviteId']) && ($inviteId = intval($_GET['inviteId'])) > 0) {
					if ($invitationDefault = TenderInvitationsDefault::findOne($inviteId)) {
						$invitationDefault->delete();
					}

					$tab = 2;
				}
			}
			
			if (isset($_POST['Invitations'])) {
				$model->isActive = 1;
				$model->save(); 
				foreach ($_POST['Invitations']['companys'] as $companyId) {				
					if (TenderInvitationsDefault::find()->where([
						'tenderDefaultId' => $model->id,
						'companyId' => $companyId
					])->count() == 0) {
						$invitationDefault = new TenderInvitationsDefault();
						$invitationDefault->tenderDefaultId = $model->id;
						$invitationDefault->companyId = $companyId;
						$invitationDefault->message = $_POST['Invitations']['message'];
						$invitationDefault->save();
					}
				}
				
				$_POST['Invitations']['emails'] = explode(',', $_POST['Invitations']['emails']);
				
				foreach ($_POST['Invitations']['emails'] as $email) {				
					if (TenderInvitationsDefault::find()->where([
						'tenderDefaultId' => $model->id,
						'email' => $email
					])->count() == 0) {
						$invitationDefault = new TenderInvitationsDefault();
						$invitationDefault->tenderDefaultId = $model->id;
						$invitationDefault->email = $email;
						$invitationDefault->message = $_POST['Invitations']['message'];
						$invitationDefault->save();
					}
				}
				
				$tab = 2;
			}
			
			if ($data = Yii::$app->request->post()) {
				if ($model->load($data)) {
					$model->type = $type;
					$model->userId = $id;
					$model->okpdsId = $_POST['Tenders']['okpdsId'];
					$model->addressIds = $_POST['Tenders']['addressIds'];

					$model->save();
					$model->setOkpds();
					$model->setAddress();
					
					$tab = 1;
				}
			}
			
			$staff = []; 
			foreach (User::find()->where(['companyId' => $user->companyId])->all() as $user) {
				$fullName = [];
				
				if ($user->lastName) $fullName[] = $user->lastName;
				if ($user->name) $fullName[] = $user->name;
				if ($user->patronymic) $fullName[] = $user->patronymic;
				
				$staff[$user->id] = join(' ', $fullName).' '.$user->userName;
			}
			
			$minsProlong = [];
			for ($i=5; $i<=1440; $i+=5) $minsProlong[$i] = $i;
			
			$countLimit = [];
			for ($i=0; $i<=1000; $i++) $countLimit[$i] = $i;
			
			return $this->render('default', [
				'file' => (isset($file)) ? $file : null,
				'user' => $user,
				'type' => $type,
				'model' => $model,
				'currency' => Currency::getAllForSelect(),
				'staff' => $staff,
				'dimensions' => Items::getForSelect(93),
				'countryCodes' => Items::getForSelect(201, 'value', 'name'),
				'adrL' => CompanyAddress::find()->where([
					'companyId' => $user->companyId, 
					'isHidden' => 0
				])->all(),
				'minsProlong' => $minsProlong,
				'countLimit' => $countLimit,
				'tab' => $tab,
				'message' => new Invitations(),
				'preInviteMessage' => 'Участник системы <b>{name}</b> ({company}) приглашает Вас к участию в торгах: {product}.',
				'invitations' => TenderInvitationsDefault::find()->where([
					'tenderDefaultId' => $model->id
				])->all()
			]);
		}
		
		return $this->redirect('/users/'.$id);
	}
	
	public function actionEdit($id='') {
		
        if($attr=Yii::$app->request->post()) {
            $attr=isset($attr['UserFormEdit'])?$attr['UserFormEdit']:$attr['User'];
            if(!$id){
                $model = new SignupForm();
        		$model->scenario = 'simple-reg';
                $model->attributes=$attr;
                if ($user = $model->signup()) {
                    $user->companyId=$attr['companyId'];
      				$user->save();
                    
                    //присваиваем новому пользователю роли и права
                    $userRole = Yii::$app->authManager->getRole('user');
                    Yii::$app->authManager->assign($userRole, $user->id);
    
                    Yii::$app->authUserManager->assignAdminRole($user);
                
                    return $this->redirect('/users/'.$user->id);
                }
            }
            else{
                //unset($attr['username']);
                $model=UserFormEdit::update($id,$attr);
            } 
            if(!$model->getErrors())    return $this->redirect('/users/'.$model->id);
        }
        else{
    		if($id && is_numeric($id)){
    			$model=UserFormEdit::load($id);
    		}
    		else{
                $model=UserFormEdit::create();
                //$model->dateCreate=date('Y-m-d H:i:s',time());
                $model->companyId=isset($_GET['cid'])?$_GET['cid']:null;
    		}
        }
        
		return $this->render('update', [
			'model' => $model,
		]);
	}
    
    public function actionSmslist($id=null) {	
	    
		$user=UserFormEdit::load($id);
        $model=new DeliverySms();
        $query=DeliverySms::find()->orderBy(["id" => SORT_DESC ])->where(['in','phone',$user->phone?$user->phone:'']);

		$dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);

        return $this->render('smsList', [
            'dataProvider' => $dataProvider,
            'user'=>$user
        ]);
	}
    
    public function actionOnline($time=5){
        $time=Yii::$app->request->post('time')?Yii::$app->request->post('time'):$time;
        $date=date("Y-m-d H:i:s", strtotime('-'.$time.' minutes'));

        $model=UserHistoryLog::find()->where(['>','dateAdd',$date])->All();
        $data=[];
        foreach($model as $el)  $data[$el->userId]=$el->id;

        $query=UserHistoryLog::find()->where(['id'=>array_reverse($data)])->with('user');
        
        $dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);
        
        $model=TendersHistory::find()->where(['>','dateAction',$date])->All();
        $data=[];
        foreach($model as $el)  $data[$el->userId]=$el->id;
        
        $query=TendersHistory::find()->where(['id'=>array_reverse($data)])->with('user');
        
        $dataProvider2 = new ActiveDataProvider([
            'query' =>$query,
        ]);
        
        if(Yii::$app->request->post('time')) $this->redirect('/users/online?time='.$time);

        return $this->render('online', [
            'dataProvider' => $dataProvider,
            'dataProvider2' => $dataProvider2,
        ]);
    }

    public function actionPermit($id)
    {
        $roles = ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'description');
        $user_permit = array_keys(Yii::$app->authManager->getRolesByUser($id));

        $model = UserFormEdit::load($id);

        return $this->render('permit', [
            'model' => $model,
            'roles' => $roles,
            'user_permit' => $user_permit,
        ]);
    }
    
    public function actionPermitUpdate($id)
    {
        $user = UserFormEdit::load($id);
        Yii::$app->authManager->revokeAll($user->getId());
        if(Yii::$app->request->post('roles')){
            foreach(Yii::$app->request->post('roles') as $role)
            {
                $new_role = Yii::$app->authManager->getRole($role);
                Yii::$app->authManager->assign($new_role, $user->getId());
            }
            Yii::$app->session->setFlash('success', Yii::t('controller', 'Настройки сохранены'));
        }
        return $this->redirect(["/users/permit", 'id' => $user->getId()]);
    }
    
    public function actionCompany($id = null){
        $userCompanyList = ArrayHelper::map(UserCompany::userCompanyList($id), 'id', 'companyId');

        if($post = Yii::$app->request->post('UserCompany')){
            if(!$post["companyId"]) $post["companyId"] = [];
            
            $forDeleteList = array_diff($userCompanyList, $post["companyId"]);
            $forAddList = array_diff($post["companyId"], $userCompanyList);

            if($forAddList){
                UserCompany::addUserCompany($id, $forAddList);
            }
            
            if($forDeleteList){
                UserCompany::delUserCompany($id, $forDeleteList);
            }

            $userCompanyList = ArrayHelper::map(UserCompany::userCompanyList($id), 'id', 'companyId');
        }
        
        $model = UserFormEdit::load($id);
        
        $modelUserCompany = new UserCompany(); 
        $modelUserCompany->companyId = $userCompanyList;
        
        return $this->render('company', [
            'model' => $model,
            'modelUserCompany' => $modelUserCompany
        ]);
    }
}