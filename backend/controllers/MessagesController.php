<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
//use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use common\models\Messages;
use common\models\User;
use yii\helpers\Url;
use common\models\MessageTypes;
use common\models\MessagesConfig;
/**
 * Site controller
 */
class MessagesController extends Controller
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

    public function actionList($id = null)
    {
        $model = new Messages();
        $query = Messages::find()->orderBy(["dateAdd" => SORT_DESC]);
        if ($id) {
            $query->where(['uid' => $id]);
            $userName = User::findOne($id);
        }
        if (isset($_GET['Messages'])) {
            $params = $_GET['Messages'];
            if ($params['uid']) {
                $uid = ArrayHelper::map(User::find()->andWhere(['like', 'name', $params['uid']])->orWhere(['like', 'lastName', $params['uid']])->orWhere(['like', 'patronymic', $params['uid']])->All(), 'id', 'id');
                $query->andWhere(['uid' => $uid]);
                $model->uid = $params['uid'];
            }
            if ($params['uidFrom']) {
                $uid = ArrayHelper::map(User::find()->andWhere(['like', 'name', $params['uidFrom']])->orWhere(['like', 'lastName', $params['uidFrom']])->orWhere(['like', 'patronymic', $params['uidFrom']])->All(), 'id', 'id');
                $query->andWhere(['uidFrom' => $uid]);
                $model->uidFrom = $params['uidFrom'];
            }
            unset($_GET['Messages']['uid']);
            unset($_GET['Messages']['uidFrom']);
            foreach ($_GET['Messages'] as $key => $el) {
                if ($el) {
                    $query->andWhere(['like', $key, $el]);
                    $model->$key = $el;
                }
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
            'userName' => $userName
        ]);
    }

    public function actionView($id = '')
    {
        if ($id && is_numeric($id)) {
            if ($model = Messages::findOne($id)) {
                if ($model['reading'] == 0 && $model['uid'] == Yii::$app->user->id) $model = Messages::setReadingStatus($id);
                return $this->render('view', [
                    'model' => $model,
                ]);
            } else $this->redirect('/messages/list');
        }
    }

    public function actionDelete($id = '')
    {
        if ($id && is_numeric($id)) {
            if (Messages::deleteall(['id' => $id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
            $this->redirect('/messages/list');
        }
    }

    public function actionUpdate($id = '')
    {
        echo $this->actionEdit($id);
    }

    public function actionCreate($id = '')
    {
        echo $this->actionEdit($id);
    }

    public function actionEdit($id = '')
    {
        $depDrop = false;
        if ($id && is_numeric($id)) {
            $model = Messages::getMessageById($id);
            //$model->users=$model->uid;
            $model->companys = $model->user->company->id;
            $depDrop = true;
        } else {
            $model = new Messages();
            $model->dateAdd = date('Y-m-d H:i:s', time());
            $model->weight = 0;
            $model->uidFrom = Yii::$app->user->id;
            $model->users = isset($_GET['uid']) ? $_GET['uid'] : null;
            $model->companys = isset($_GET['cid']) ? $_GET['cid'] : null;
            if ($model->companys && !$model->users) $model->users = 'all';
            if ($model->users || $model->companys) $depDrop = true;
        }

        if (isset($_POST["Messages"])) {
            //Messages::sendMessage($_POST['Messages']);
            $model->attributes = $_POST["Messages"];
            if ($model->validate()) $model->save();
        }

        return $this->render('update', [
            'model' => $model,
            'depDrop' => $depDrop
        ]);
    }
    
    public function actionTypes()
    {

        $dataProvider = new ActiveDataProvider([
            'query' => MessageTypes::find(),
        ]);
        
        return $this->render('types', compact('dataProvider'));
    }
    
    public function actionTypeEdit()
    {
        $id = Yii::$app->request->get('id');
        return $this->typeEdit($id);
    }
    
    public function actionTypeAdd()
    {
        return $this->typeEdit();
    }
    
    private function typeEdit($id = null)
    {
        if ($id) {
            $model = MessageTypes::findOne($id);
        } else {
            $model = new MessageTypes(['isAuto' => 1]);
        }
        
        if ($post = Yii::$app->request->post('MessageTypes')) {
            $model->name = $post['name'];

            if ($model->save()) {
               $levels = $post['level'];
                
                foreach ($levels as $key => $el) {
                    if (!$el) {
                        unset ($levels[$key]);
                    } else {
                        $levels[$key] = $key;
                    }
                }    
                
                if ($levels) {
                    $config = [$model->id => $levels];
                    
                    $userList = User::allByParams(['forDelete' => 0]);
                    foreach ($userList as $el) {
                        MessagesConfig::setConfigAdmin($config, $el->id);
                    }
                    
                }
            }
            Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
        }
        
        return $this->render('type_edit', compact('model')); 
    }

    public function actionSelect()
    {
        Messages::dependenSelectAction();
    }

    public function actionSelectDelivery()
    {
        Messages::dependenSelectDeliveryAction();
    }

}