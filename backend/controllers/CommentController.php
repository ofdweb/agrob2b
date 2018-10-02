<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use common\components\THelper;

use common\models\CompanyComment;
use common\models\Company;
use common\models\User;
use yii\helpers\Url;
/**
 * Site controller
 */
class CommentController extends Controller
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
        $query=CompanyComment::itemListProvider();

		$dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);

        return $this->render('list', [
            'dataProvider' => $dataProvider,
        ]);
	}
    
    public function actionView($id=null){
        $model=CompanyComment::oneById($id);
        return $this->render('view', [
            'model' => $model,
        ]);
    }
    
    public function actionDelete($id=null){
        CompanyComment::delById($id);
        return $this->redirect('/comment/list');
    }
    
    public function actionCreate($cid=null){
        $result=$this->actionEdit($id=null,$cid);
        return $this->render('_form', $result); 
    }
    
    public function actionUpdate($id=null){
        $result=$this->actionEdit($id);
        return $this->render('_form', $result); 
    }
    
    public function actionEdit($id=null,$cid=null){       
        if($id && is_numeric($id)){
            $model=CompanyComment::oneById($id);
        }
  		else{
            $model=new CompanyComment();
            if($cid) $model->companyId=$cid;
  		}
        
        if(isset($_POST["CompanyComment"])){
            $model->attributes=$_POST["CompanyComment"];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/comment/list');
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
        }
        
        $companyList=ArrayHelper::map(Company::itemList(), 'id', 'nameShort');
        $userList=THelper::cmap(User::itemList(), 'id', ['lastName','name','company.nameShort'],' ');
        
        return [
            'model'=>$model,
            'companyList'=>$companyList,
            'userList'=>$userList
        ];
    }
    
}