<?php

namespace backend\modules\Dictionary\controllers;

use backend\modules\Dictionary\models\DictionaryCategory;
use Yii;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers;
use yii\helpers\ArrayHelper;

class CategoryController extends Controller
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
        $itemTree=DictionaryCategory::tree();
        return $this->render('index',[
            'itemTree'=>$itemTree
        ]);
    }
    
    public function actionView($id=null){
        if($id){
            $model=DictionaryCategory::oneById($id);
            return $this->render('_view', [
                'model' => $model,
            ]);
        }
    }
    
    public function actionCreate($id=null,$parentId=null)
    {
        echo $this->_formEdit($id,$parentId);
    }
    
    public function actionUpdate($id=null,$del=null)
    {
        if($del){
            DictionaryCategory::delOneById($id);
            return $this->redirect('/dictionary/category');
        }
        echo $this->_formEdit($id);
    }
    
    public function _formEdit($id=null,$parentId=null)
    {
        if($id){
	       $model=DictionaryCategory::oneById($id);
        }
  		else{
            $model=new DictionaryCategory();
            $model->parentId=$parentId;
            $model->creatorId=Yii::$app->user->id;
  		}
        
        if($attr=Yii::$app->request->post()){
            $model->attributes=$attr['DictionaryCategory'];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');  
                    return $this->redirect('/dictionary/category');  
                }
            }
        }
        
        $parentList=[0=>'-корень-']+ArrayHelper::map(DictionaryCategory::itemList(), 'id', 'name');
        return $this->render('_formEdit', [
			'model' => $model,
            'parentList'=>$parentList,
		]);
    }
}
