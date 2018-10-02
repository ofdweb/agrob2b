<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

use common\models\DeliverySms;
use backend\models\UserFormEdit;
use yii\helpers\Json;
use yii\helpers\Url;
/**
 * Site controller
 */
class SmsController extends Controller
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
        $model=new DeliverySms();
        $query=DeliverySms::find()->orderBy(["id" => SORT_DESC ]);

		$dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);

        $balance=$model::getBalance();

        return $this->render('list', [
            'dataProvider' => $dataProvider,
            'balance'=>$balance
        ]);
	}
    
    public function actionInfo(){
        $attr=Yii::$app->request->post();
        $phone=$attr['phone'];

        if($phone){
            $model=new DeliverySms();
            $operator=$model::getOperator($phone);
            $hlr=$model::getHlr($phone);

            $params=[
                'model'=>$model,
                'operator'=>$operator->operator,
                'hlr'=>$hlr[0]
            ];
            
            if(Yii::$app->request->isAjax)  return $this->renderAjax('_info', $params);
            else return $this->render('_info',$params);     
        }
        else return 'Не указан номер телефона';
    }
    
    public function actionOperator(){
        $attr=Yii::$app->request->post();
        $phone=$attr['phone'];

        if($phone){
            $model=new DeliverySms();
            //$hlr=$model::getHlr($phone);
            $operator=$model::getOperator($phone);
            //return  $hlr[0]->orn;
            return $operator->operator;
        }
        else return 'Не указан номер телефона';
    }

	public function actionView($id='')
	{
		if($id && is_numeric($id)){
		  
		  $model=DeliverySms::oneById($id);
          $userModel=UserFormEdit::oneByPhone($model->phone);       
		  $balance=$model::getBalance();
          
          return $this->render('_view', [
		      'model' => $model,
              'userModel'=>$userModel,
              'balance'=>$balance
            ]);
        }
	}

    public function actionDelete($id=null)
	{
            DeliverySms::delOneById($id);
            return $this->redirect('/sms/list');
	}
    
    public function actionStatus($id=null)
	{
            DeliverySms::getStatus($id);
            return $this->redirect('/sms/'.$id);
	}
		
	public function actionUpdate($id=null)
	{
		echo $this->actionEdit($id);
	}
	public function actionCreate($id='',$uid=null,$mult=false)
	{
		echo $this->actionEdit($id,$uid,$mult);
	}
	
	public function actionEdit($id='',$uid=null,$mult=false)
	{
        if($id && is_numeric($id)){
	       $model=DeliverySms::oneById($id);
        }
  		else{
            $model=new DeliverySms();
            $model->service=Yii::$app->params['smsService'];
            if($uid){
                $user=UserFormEdit::load($uid);
                $model->phone=$user->phone;
            }
  		}
        
        if(isset($_POST["DeliverySms"])){
            $model->attributes=$_POST["DeliverySms"];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/sms/list');
            }
        }
        
        $userModel=!$user?UserFormEdit::oneByPhone($model->phone):$user;
        $userList=$mult?UserFormEdit::itemListPhone():null;
        $balance=$model::getBalance();
        $serviceList=$model::serviceList();
        
		return $this->render('_form', [
			'model' => $model,
            'userModel'=>$userModel,
            'mult'=>$mult,
            'userList'=>$userList,
            'balance'=>$balance,
            'serviceList'=>$serviceList,
		]);
	}

}