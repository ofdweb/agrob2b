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
class PermissionController extends Controller {

	public function beforeAction($action){
		//if(defined('YII_DEBUG') && YII_DEBUG){
			Yii::$app->assetManager->forceCopy = true;
		//}
		return parent::beforeAction($action);
	}

	public function actionList() {
		
		$dataProvider = new ActiveDataProvider(['query' => Permissions::find()]);
		$permission = new Permissions();
		return $this->render('list', ['dataProvider' => $dataProvider, 'permission' => $permission]);	
	}
	
	public function actionEdit($id='') {
		if ($attr = Yii::$app->request->post()) {
			if(!empty($id)) {
				Permissions::updateAll($attr['Permissions'], ['id' => $id]);
			} else {
				
				$permission = new Permissions();
				$permission -> attributes = $attr['Permissions'];
				$permission -> save();
			}
            
            Yii::$app->session->setFlash('success','Сохранено!');
            
            return $this->redirect('/accespanel/permission/list');
		}
		
		if ($id && is_numeric($id)){
			$model = Permissions::find()->where(['id'=>$id])->one();
		} else {
			$model = new Permissions();
		}
		
		return $this->render('edit', [
			'model' => $model
		]);
	}
	
	public function actionDelete($id) {
		
		if ($id && is_numeric($id)){
			Permissions::deleteAll('id = :id', [':id' => $id]);
			PermissionApp::deleteAll('idPermission = :idPermission', [':idPermission' => $id]);
		}
		
        Yii::$app->session->setFlash('success','Удалено!');
		return $this->redirect('/accespanel/permission/list');
	}
	
}
?>