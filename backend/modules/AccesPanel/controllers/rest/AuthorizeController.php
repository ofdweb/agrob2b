<?php
namespace backend\modules\AccesPanel\controllers\rest;

use Yii;
use yii\rest\ActiveController;
use backend\modules\AccesPanel\components\B2bOauth2;
use backend\modules\AccesPanel\models\App;
use backend\modules\AccesPanel\models\Codes;
use backend\modules\AccesPanel\models\Tokens;
use common\components\Encrypt;
use common\models\LoginForm;
use common\models\User;
use common\models\DeliverySms;
use common\components\HelperOauth;
use common\models\UserAuthCodes;
use yii\web\BadRequestHttpException;
use common\components\THelper;

class AuthorizeController extends ActiveController
{
    public $modelClass = 'common\models\User';
            
    public function behaviors()
	{
		$behaviors = parent::behaviors();
		return $behaviors;
	}
    
    public function beforeAction($action) 
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->language = 'ru_RU';
        return parent::beforeAction($action);
    }
    
    public function actionAuth() 
    {
        if (Yii::$app->request->isGet) {
            $params = Yii::$app->request->get();

            \Yii::$app->response->format = $this->setFormat($params);
            
            if ($error = $this->paramsErrors($params)) {
                return $this->returnError($error);
            }

            if ($params['response_type'] == 'code') {
                if (!$params['redirect_uri']) {
                    return $this->returnError('invalid_uri');
                }
                
                $oauthClient = new B2bOauth2();
                $oauthClient->load($params);
                $url = $oauthClient->buildAuthUrl(B2bOauth2::OAUTH_URL, $params);

                return [
                    'redirect' => $url
                ];    
            } elseif ($params['response_type'] == 'phone') {
                if (!$params['number']) {
                    return $this->returnError('invalid_phone_number');
                }
                
                $phone = str_replace(['+', ' ', '(', ')', '-', '/', '.', ','], '', $params['number']);

                if (!$phone) {
                    return $this->returnError('bad_phone_number');
                } else {
                    if ($phone[0] == '8') {
                        //$phone[0] = '7';
                    }

                    $data = DeliverySms::getOperator($phone);

                    if (!$data->operator) {
                        return $this->returnError('bad_phone_number');
                    }
                }
                
                //$phone = '+' . $phone;
                $phone = trim(str_replace(THelper::$phoneCode, null, $phone));
                $user = User::find()->where(['LIKE', 'phone', $phone])->one();
                
                if (!$user) {
                    return $this->returnError('user_not_found');
                }
                
                $phoneCode = UserAuthCodes::generateNewCode($user->id);
                if (!$phoneCode) {
                    return $this->returnError('time_phone_code');
                }
                
                UserAuthCodes::updateAll(['appClientId' => $params['client_id']], ['id' => $phoneCode->id]);
                $phoneCode->sendAuthCode($user->phone);
                
                return [
                    'code' => $phoneCode->code,
                    'expires_in' => $phoneCode->expire,
                ];
            }
            
            return $this->returnError('invalid_response_type');
        }  
        
        return $this->returnError('invalid_request_type');
    }
    
    public function actionOauth() 
    {
        $model = new LoginForm();
        
        if ($model->load(Yii::$app->request->post())) {
            $model->username = trim($model->username);
            
			if ($modelUser = User::findByUserName($model->username)) {
                $modelUser->updateToken();
			}   
            
            if (!$model->login()) {
                Yii::$app->session->setFlash('error', Yii::t('controller', 'Не верный логин или пароль'));
            }
            
            return $this->refresh();
        }
        
        if (Yii::$app->request->isGet) {
            $params = Yii::$app->request->get();
            \Yii::$app->response->format = $this->setFormat($params);
            
            if ($error = $this->paramsErrors($params)) {
                return $this->returnError($error);
            } elseif (!$params['redirect_uri']) {
                return $this->returnError('invalid_uri');
            }
            
            $app = App::oneWhithPermis(['id' => $params['client_id']]);
            
            $oauthClient = new B2bOauth2();
            $oauthClient->load($params);
            $url = $oauthClient->buildAuthUrl(B2bOauth2::CODE_URL, $params);
            
            $uidEncrypt = null;
            if (!Yii::$app->user->isGuest) {
                $security = new Encrypt(Codes::USER_ENCRYPT);
                $uidEncrypt = $security->encode(Yii::$app->user->id);    
            }

            \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
            $this->layout = "oauth";

            return $this->render('index', compact('url', 'app', 'uidEncrypt', 'model')); 
        }
        
        return $this->returnError('invalid_request_type');                
    }
    
    public function actionRefresh()
    {
        if (Yii::$app->request->isPost) {
            $params = Yii::$app->request->post();
         //   \Yii::$app->response->format = $this->setFormat($params);
             
            if ($params['grant_type'] == 'refresh') {
                if (!$params['refresh_token']) {
                    return $this->returnError('invalid_refresh_token');
                } 
                
                $token = Tokens::refreshAccessToken($params['refresh_token']);
                
                if ($token->id) { 
                    return [
                        'access_token' => $token->token,
                        'refresh_token' =>  $token->refreshToken,
                        'expires_in' => $token->dateTo,
                        'token_type' => 'Bearer'
                    ];     
                }
                
                return $this->returnError('not_save_token');
            }
            
            return $this->returnError('invalid_grant_type');
        }
        
        return $this->returnError('invalid_request_type');
    }
    
    public function actionToken()
    {
        if (Yii::$app->request->isPost) {
            $params = Yii::$app->request->post();
            $code = null;

            \Yii::$app->response->format = $this->setFormat($params);

            if ($params['grant_type'] == 'authorization_code') {
                if ($error = $this->paramsErrors($params)) {
                    return $this->returnError($error);
                }
            
                if (!$params['code']) {
                    return $this->returnError('invalid_code');
                } elseif (!$userId = Codes::userIdByParams(['code' => $params['code'], 'clientId' => $params['client_id']])) {
                    return $this->returnError('not_exist_code');
                } elseif (!Codes::checkExpires($params['code'])) {
                    return $this->returnError('invalid_expires_code');
                } elseif (!$params['client_secret']) {
                    return $this->returnError('invalid_client_secret');
                } /*elseif (!App::existByParams(['id' => $params['client_id'], '_key' => $params['client_secret']])) {
                    return $this->returnError('not_exist_client');
                }    */
                
                $code = $params['code'];
                
            } elseif ($params['grant_type'] == 'password') {
                if ($error = $this->paramsErrors($params)) {
                    return $this->returnError($error);
                }
                                
                if (!$params['username']) {
                    return $this->returnError('invalid_username');
                } elseif (!$params['password']) {
                    return $this->returnError('invalid_password');
                } elseif (!$modelUser = User::findByUsername($params['username'])) {
                    return $this->returnError('not_validate_username');
                } elseif (!$modelUser->validatePassword($params['password'])) {
                    return $this->returnError('not_validate_password');
                } elseif (!$params['client_secret']) {
                    return $this->returnError('invalid_client_secret');
                } /*elseif (!App::existByParams(['id' => $params['client_id'], '_key' => $params['client_secret']])) {
                    return $this->returnError('not_exist_client');
                }*/
                
                $userId = $modelUser->id;
                
            } elseif ($params['grant_type'] == 'phone_code') {
                if (!$params['code']) {
                    return $this->returnError('invalid_code');
                }
                
                $phoneCode = UserAuthCodes::checkCodeAuthorize($params['code']);
                if (!$phoneCode) {
                    return $this->returnError('invalid_expires_code');
                }
                
                $params['client_id'] = $phoneCode->appClientId;
                $userId = $phoneCode->userId;
                $code = $phoneCode->code;
                
            } else {
                return $this->returnError('invalid_grant_type');
            }
            
            $token = new Tokens([
                'idUser' => $userId,
                'idApp' => $params['client_id'],
                'code' => $code
            ]);
            
            $token->fetchAccessToken();
                
            if ($token->id) {
                return [
                    'access_token' => $token->token,
                    'refresh_token' =>  $token->refreshToken,
                    'expires_in' => $token->dateTo,
                    'token_type' => 'Bearer'
                ];     
            }
            
            return $this->returnError('not_save_token'); 
        } 
        
        return $this->returnError('invalid_request_type');
    }
    
    public function actionCode()
    {
        if (Yii::$app->request->isGet) {
            $params = Yii::$app->request->get();
            \Yii::$app->response->format = $this->setFormat($params);
            
            $uid = Yii::$app->user->id;
            $security = new Encrypt(Codes::USER_ENCRYPT);
            $paramsUid = $security->decode($params['uid']);

            if (Yii::$app->user->isGuest) {
                return $this->returnError('is_guest');
            } elseif ($error = $this->paramsErrors($params)) {
                return $this->returnError($error);
            } elseif (!$params['redirect_uri']) {
                return $this->returnError('invalid_uri');
            } elseif (!$params['uid'] || $uid != $paramsUid) {
                return $this->returnError('invalid_uid');
            }
            
            $oauthClient = new B2bOauth2();
            $oauthClient->load($params);
            
            if ($oauthClient->validateAuthState) {
                $h = $oauthClient->generateAuthHash([
                    $oauthClient->clientId,
                    $oauthClient->redirectUri,
                    $oauthClient->state
                ]);
                
                if (!$params['h']) {
                    return $this->returnError('invalid_auth_hash');
                } elseif ($params['h'] != $h) {
                    return $this->returnError('not_equal_auth_hash');
                }
            }
            
            if (isset($params['cancel'])) {
                return $this->returnError('auth_user_cancel');
            }
            
            if (!$codeModel = Codes::oneExpires($params)) {
                $codeModel = Codes::add([
                    'clientId' => $params['client_id'],
                    'redirectUri' => $params['redirect_uri'],
                    'userId' => $uid
                ]);
                
                if (!$codeModel) {
                    return $this->returnError('not_save_code');
                }    
            } 

            $url = $oauthClient->composeUrl($params['redirect_uri'], ['code' => $codeModel->code]);
            return Yii::$app->getResponse()->redirect($url);
        }
        
        return $this->returnError('invalid_request_type');
    }
    
    private function returnError($errorKey = null)
    {
        /*
        if (!$errorKey || !($errorDesc = HelperOauth::$errorList[$errorKey])) {
            $errorKey = 'none';
            $errorDesc = HelperOauth::$errorList[$errorKey];
        }
        
        return [
            'error' => $errorKey,
            'error_description' => Yii::t('app', $errorDesc)
        ];  
        */
        
        if (!$errorKey) {
            $errorKey = 'none';
        }
        
        $code = HelperOauth::$errorList[$errorKey];
        throw new BadRequestHttpException(Yii::t('app',  HelperOauth::$errorListText[$code]), $code);
        
        return [
            'error' => HelperOauth::$errorList[$errorKey]
        ];

    }
    
    private function paramsErrors($params = [])
    {
        if (!$params) {
            return 'none_params';
        } elseif (!$params['client_id']) {
            return 'invalid_client';
        } elseif (!$params['client_secret']) {
            return 'invalid_client_secret';
        } elseif (!App::existByParams(['id' => $params['client_id'], '_key' => $params['client_secret']])) {
            return 'not_exist_client';
        }
        
        return false;
    }
    
    private function setFormat($params = [])
    {
        switch($params['format'])
        {
            case 'xml':
                $format = \yii\web\Response::FORMAT_XML;
            break;
            case 'json':
            default:
                $format = \yii\web\Response::FORMAT_JSON;
            break;
        }
        
        return $format;
    }
}