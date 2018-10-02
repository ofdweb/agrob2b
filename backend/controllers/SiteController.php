<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Url;

use common\models\LoginForm;

use common\models\User;
use common\models\Tenders;
use common\models\TenderLots;
use common\models\UserHistoryLog;
use common\models\TendersHistory;
use common\models\TarifList;
use common\models\Billing;
use common\models\TarifListJournal;
use common\models\Company;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \backend\behaviors\AccessBehavior::className(),
                'rules' => [
                    'site' => [
                        [
                            'allow' => true,
                            'actions' => ['login', 'error'],
                            'roles' => ['?'],
                        ],
                        [
                            'allow' => true,
                            'roles' => ['@'],
                            'actions' => ['*']
                        ],
                    ]
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->getResponse()->redirect(Yii::$app->UrlManager->createUrl(Url::to('login')));
                }
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


    /*public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {

            if ($action->id!='login' && !Yii::$app->user->can('administration')) {
                if (!\Yii::$app->user->isGuest) {
                    return $this->goHome();
                }

                $model = new LoginForm();
                if ($model->load(Yii::$app->request->post()) && $model->login()) {
                    return $this->goBack();
                } else {
                    return $this->render('login', [
                        'model' => $model,
                    ]);
                }
                exit;
            }
            //Yii::$app->getResponse()->redirect('login');
            exit;
        } else {
            return false;
        }
    }*/

    public function actionIndex()
    {

        return $this->render('index');
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            if ($user = User::findByUserName($model->username)) {
                $user->updateToken();
            }

            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }


    public function actionUpload()
    {
        return $this->renderPartial('upload', [
            //'model' => $model,
        ]);
    }

    public function actionUploadify()
    {
        require($_SERVER['DOCUMENT_ROOT'] . '/js/plugins/upload/server/php/UploadHandler.php');

        $upload_handler = new \UploadHandler([
            'itemId' => 112,
            'itemId' => 'product',
            'script_url' => '/site/upload',
            'upload_dir' => $_SERVER['DOCUMENT_ROOT'] . '/uploads',
            'upload_url' => '/uploads',
            'image_versions' => array(
                'original' => array(
                    'upload_dir' => $_SERVER['DOCUMENT_ROOT'] . '/uploads/',
                    'upload_url' => '/uploads/',
                ),

                'medium' => array(
                    'upload_dir' => $_SERVER['DOCUMENT_ROOT'] . '/uploads/medium/',
                    'upload_url' => '/uploads/medium/',
                    'max_width' => 800,
                    'max_height' => 600
                ),

                'thumbnail' => array(
                    // Uncomment the following to use a defined directory for the thumbnails
                    // instead of a subdirectory based on the version identifier.
                    // Make sure that this directory doesn't allow execution of files if you
                    // don't pose any restrictions on the type of uploaded files, e.g. by
                    // copying the .htaccess file from the files directory for Apache:
                    'upload_dir' => $_SERVER['DOCUMENT_ROOT'] . '/uploads/thumb/',
                    'upload_url' => '/uploads/thumb/',
                    // Uncomment the following to force the max
                    // dimensions and e.g. create square thumbnails:
                    //'crop' => true,
                    'max_width' => 80,
                    'max_height' => 80
                )
            ),
        ]);

        exit(0);
    }

    public function actionLogout()
    {

        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionInfo()
    {
        echo phpinfo();
    }
}
