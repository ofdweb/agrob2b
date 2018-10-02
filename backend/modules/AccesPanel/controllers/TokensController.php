<?php
namespace backend\modules\AccesPanel\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\components\THelper;
use common\models\User;

use backend\modules\AccesPanel\models\App;
use backend\modules\AccesPanel\models\PermissionApp;
use backend\modules\AccesPanel\models\Permissions;
use backend\modules\AccesPanel\models\Tokens;

/**
 * Site controller
 */
class TokensController extends Controller {

	public function beforeAction($action){
		//if(defined('YII_DEBUG') && YII_DEBUG){
			Yii::$app->assetManager->forceCopy = true;
		//}
		return parent::beforeAction($action);
	}

	public function actionList() {
		$token = new Tokens();
		$dataProvider = new ActiveDataProvider(['query' => Tokens::find()->orderBy(['id' => SORT_DESC])]);
		return $this->render('list', ['dataProvider' => $dataProvider, 'token' => $token]);
	}
	
	public function actionCreate() {
		$model = new Tokens();
		
		if ($attr = Yii::$app->request->post()) {
			$users = $attr['User']['id'];
			$App = $attr['App']['id'];
			$Permission = $attr['Permission']['id'];
			foreach ($users as $user) {
				$token = new Tokens([
					'idUser' => $user,
					'idApp' => $App,
					'permissios' => $Permission,
					'dateTo' => $attr['dateTo']
				]);
				
				$token->fetchToken();
			}
			
			if ($token->id) {	
				Yii::$app->session->setFlash('success','Токен создан');
				return $this->redirect('/accespanel/tokens/list');
			}
		}

		$userList = THelper::cmapCompany(User::find()->all(), 'id', ['name', 'lastName'], ' ');
		$permissionList = ArrayHelper::map(Permissions::find()->all(),'id','permissionText');
		$appList = ArrayHelper::map(App::find()->all(),'id','appName');
		
		$modelUserList = new User();
		$modelPermissionList = new Permissions();
		$modelAppList = new App();
		
		return $this->render('create',[
			'model' => $model,
			'userList' => $userList,
			'permissionList' => $permissionList,
			'appList' => $appList,
			'modelUserList' => $modelUserList,
			'modelPermissionList' => $modelPermissionList,
			'modelAppList' => $modelAppList,
		]);
	}
	
	public function actionGetDefaultsPermission($id='') {
		if($id && is_numeric($id)) {
			$Permission = ArrayHelper::map(PermissionApp::find()->where(['idApp' => $id])->all(),'idPermission','idPermission');
			return implode(',',$Permission);
		}
	}
	
	public function actionGetDefaultDateTo($id='') {
		if($id && is_numeric($id)) {
			$appDate = App::find()->where(['id' => $id])->one()->dateTo;
			return $appDate;
		}
	}
}
?>