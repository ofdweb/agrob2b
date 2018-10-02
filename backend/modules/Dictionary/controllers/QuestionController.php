<?php

namespace backend\modules\Dictionary\controllers;

use backend\modules\Dictionary\models\DictionaryQuestion;
use Yii;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers;
use yii\helpers\ArrayHelper;

class QuestionController extends Controller
{
    public function behaviors()
    {
        return [
			'access' => [
				'class' => AccessControl::className(),
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
			],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }
    
    public function beforeAction($action){
        Yii::$app->assetManager->forceCopy = true;
        return parent::beforeAction($action);
    }
    
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' =>DictionaryQuestion::gridProvider(),
        ]);
        
        return $this->render('index',[
            'dataProvider' => $dataProvider,
        ]);
    }
    
    public function actionView($id=null){
        if($id){
            $model=DictionaryQuestion::oneById($id);
            return $this->render('_view', [
                'model' => $model,
            ]);
        }
    }
    
    public function actionDelete($id=null,$parentId=null)
    {
        if(Yii::$app->request->post()){
            DictionaryQuestion::delOneById($id);
            return $this->redirect('/dictionary/question');
        }
    }
}
