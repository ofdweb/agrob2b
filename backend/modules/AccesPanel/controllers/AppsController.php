<?php
namespace backend\modules\AccesPanel\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use backend\modules\AccesPanel\models\App;
use backend\modules\AccesPanel\models\PermissionApp;
use backend\modules\AccesPanel\models\Permissions;
use backend\modules\AccesPanel\models\Tokens;

/**
 * Site controller
 */
class AppsController extends Controller {

	public function beforeAction($action){
		//if(defined('YII_DEBUG') && YII_DEBUG){
			//Yii::$app->assetManager->forceCopy = true;
		//}
		return parent::beforeAction($action);
	}

	public function actionList() {
		$dataProvider = new ActiveDataProvider(['query' => App::find()]);
		$app = new App();
		return $this->render('list',['dataProvider' => $dataProvider, 'app' => $app]);
	}
	
	public function actionEdit($id='') {
		
		if ($attr = Yii::$app->request->post()) {
			
			if (!empty($id)) {
				
				App::updateAll(['appName' => $attr['App']['appName'], 'dateTo' => $attr['App']['dateTo']], ['id' => $id]);
				PermissionApp::deleteAll('idApp = :idApp', [':idApp' => $id]);
			
				foreach ($attr['PermissionApp']['idPermission'] as $item) {
					$model = new PermissionApp();
					$model -> idApp = $id;
					$model -> idPermission = $item;
					$model -> save();
				}
				
			} else {
				
				$app = new App();
				$app->attributes = $attr['App'];
				$app->_key = App::generateRandomString();
				$app->save();
				
				foreach ($attr['PermissionApp']['idPermission'] as $item) {
					$model = new PermissionApp();
					$model -> idApp = $app->id;
					$model -> idPermission = $item;
					$model -> save();
				}
				
			}
			
			Yii::$app->session->setFlash('success','Сохранено!');
            
            return $this->redirect('/accespanel/apps/list');
		}
		
		if ($id && is_numeric($id)){
			$model = App::find()->where(['id'=>$id])->one();
		} else {
			$model = new App();
		}
		
		$PermissionApp = new PermissionApp();
		$PermissionApp->idPermission = ArrayHelper::map(PermissionApp::find()->where(['idApp' => $id])->all(),'id','idPermission');
		$permissionList = ArrayHelper::map(Permissions::find()->all(),'id','permissionText');
		
		return $this->render('edit', [
			'model' => $model,
			'permissionApp' => $PermissionApp,
			'permissionList' => $permissionList
		]);
	}
	
	public function actionDelete($id) {
		
		if ($id && is_numeric($id)){
			App::deleteAll('id = :id', [':id' => $id]);
			PermissionApp::deleteAll('idApp = :idApp', [':idApp' => $id]);
		}
		
		Yii::$app->session->setFlash('success','Удалено!');
		
		return $this->redirect('/accespanel/apps/list');
	}
	
}
?>