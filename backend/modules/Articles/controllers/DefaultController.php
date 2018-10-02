<?php

namespace backend\modules\Articles\controllers;

use Yii;
use backend\modules\Articles\models\Article;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers;

/**
 * DefaultController implements the CRUD actions for Article model.
 */
class DefaultController extends Controller
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

    /**
     * Lists all Article models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Article::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Article model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Article model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Article();

        if ($model->load(Yii::$app->request->post())) {			
			if (trim($model->url) == '') {
				$model->url = \yii\helpers\BaseInflector::slug($model->title);

				// Приблуда для добавления постфикса в случае совпадения Урла, не выводит ошибку в форме
				/*
				$model->validate(); 
				$n = 2;
				while (isset($model->errors['url']) && count($model->errors['url'])>0) {
					$model->url = \yii\helpers\BaseInflector::slug($model->title).'-'.$n;
					$model->validate();
					$n++;
				}
				*/
			}
			
			
			
			if ($model->validate() && $model->save()) {
				return $this->redirect(['view', 'id' => $model->id]);
			}
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Article model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
			
			if (trim($model->url) == '') {
				$model->url = \yii\helpers\BaseInflector::slug($model->title);

				// Приблуда для добавления постфикса в случае совпадения Урла, не выводит ошибку в форме
				/*
				$model->validate(); 
				$n = 2;
				while (isset($model->errors['Url']) && count($model->errors['Url'])>0) {
					$model->Url = \yii\helpers\BaseInflector::slug($model->Title).'-'.$n;
					$model->validate();
					$n++;
				}
				*/
			}
			
			if ($model->validate()) {
				if ($model->save()) {
					return $this->redirect(['view', 'id' => $model->id]);
				}
			} else {
				return $this->render('update', [
					'model' => $model,
					'errors' => $errors
				]);
			}
			
            return $this->redirect(['view', 'id' => $model->id]);
        } 
		
		return $this->render('update', [
			'model' => $model
		]);
    }

    /**
     * Deletes an existing Article model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Article model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Article the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Article::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
