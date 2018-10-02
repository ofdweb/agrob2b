<?php

namespace backend\controllers;

use Yii;
use common\models\CompanyContracts;
use common\models\CompanyTransporter;
use common\models\CompanyShipper;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;



/**
 * ContractsController implements the CRUD actions for CompanyContracts model.
 */
class ContractsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
				'class' => \backend\behaviors\AccessBehavior::className(),
			],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all CompanyContracts models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => CompanyContracts::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single CompanyContracts model.
     * @param integer $transporterId
     * @param integer $shipperId
     * @return mixed
     */
    public function actionView($transporterId, $shipperId)
    {
        return $this->render('view', [
            'model' => $this->findModel($transporterId, $shipperId),
        ]);
    }

    /**
     * Creates a new CompanyContracts model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new CompanyContracts();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'transporterId' => $model->transporterId, 'shipperId' => $model->shipperId]);
        } else {
            return $this->render('create', [
                'model' => $model,
				'transporters' => ArrayHelper::map(CompanyTransporter::find()->select(['name', 'id'])->asArray()->all(), 'id', 'name'),
				'shippers' => ArrayHelper::map(CompanyShipper::find()->select(['name', 'id'])->asArray()->all(), 'id', 'name'),
            ]);
        }
    }

    /**
     * Updates an existing CompanyContracts model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $transporterId
     * @param integer $shipperId
     * @return mixed
     */
    public function actionUpdate($transporterId, $shipperId)
    {
        $model = $this->findModel($transporterId, $shipperId);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'transporterId' => $model->transporterId, 'shipperId' => $model->shipperId]);
        } else {
            return $this->render('update', [
                'model' => $model,
				'transporters' => ArrayHelper::map(CompanyTransporter::find()->select(['name', 'id'])->asArray()->all(), 'id', 'name'),
				'shippers' => ArrayHelper::map(CompanyShipper::find()->select(['name', 'id'])->asArray()->all(), 'id', 'name'),
            ]);
        }
    }

    /**
     * Deletes an existing CompanyContracts model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $transporterId
     * @param integer $shipperId
     * @return mixed
     */
    public function actionDelete($transporterId, $shipperId)
    {
        $this->findModel($transporterId, $shipperId)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the CompanyContracts model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $transporterId
     * @param integer $shipperId
     * @return CompanyContracts the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($transporterId, $shipperId)
    {
        if (($model = CompanyContracts::findOne(['transporterId' => $transporterId, 'shipperId' => $shipperId])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
