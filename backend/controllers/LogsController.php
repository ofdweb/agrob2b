<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;

use yii\helpers\ArrayHelper;
use common\models\ActionLogs;
use common\components\THelper;
use yii\helpers\Url;
/**
 * Site controller
 */
class LogsController extends Controller
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
	
	public function actionList()
	{	
        $model=new ActionLogs();
        
        $query=ActionLogs::itemListProveder();
        $listModel=$query->asArray()->all();
        
        if($attr=$_GET){
            if($attr['uid']) $query->andFilterWhere(['like', 'uid', $attr['uid']]);
            if($attr['action']) $query->andFilterWhere(['like', 'action', $attr['action']]);
            if($attr['ip']) $query->andFilterWhere(['like', 'ip', $attr['ip']]);
            if($attr['model']) $query->andFilterWhere(['like', 'model', $attr['model']]);
            if($attr['dateAdd']) $query->andFilterWhere(['like', 'dateAdd', $attr['dateAdd']]);
            if($attr['targetId']) $query->andFilterWhere(['targetId'=> $attr['targetId']]);
        }
        
		
        $dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);
        
        //$listModel=$model->itemList();
        $list['user']=[0=>'Все']+THelper::cmap($listModel, 'uid', ['user.lastName','user.name'],' ');
        $list['action']=[0=>'Все']+ArrayHelper::map($listModel, 'action', 'action');
        $list['ip']=[0=>'Все']+ArrayHelper::map($listModel, 'ip', 'ip');
        $list['model']=[0=>'Все']+ArrayHelper::map($listModel, 'model', 'model');

        return $this->render('list', [
            'dataProvider' => $dataProvider,
            'model'=>$model,
            'list'=>$list
        ]);
	}
}