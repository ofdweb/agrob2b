<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace backend\modules\AccesPanel\components;

use Yii;
use yii\base\Component;

class B2bOauth2 extends Component
{
    const DISPLAY_PAGE = 'page';
    const DISPLAY_POPUP = 'popup ';
    const DISPLAY_MOBILE = 'mobile';
    
    const VERSION = '1.0';
    
    const BASE_URL = 'http://rigoath.nawww.ru/accespanel/rest/authorize';
    const OAUTH_URL = B2bOauth2::BASE_URL . '/oauth';
    const TOKEN_URL = B2bOauth2::BASE_URL . '/token';
    const CODE_URL = B2bOauth2::BASE_URL . '/code';
    
    public $clientId;
    public $clientSecret;
    public $redirectUri;
    public $code;
    public $display = B2bOauth2::DISPLAY_PAGE;
    public $state;
    public $grant_type;
    public $validateAuthState = true;
    
    private $_httpClient = 'yii\httpclient\Client';
    private $_requestOptions = [];
    
    public static $errorList = [
        'none' => 'неизвестная ошибка',
        'none_params' => 'не указаны параметры запроса',
        'invalid_response_type' => 'не верный response_type',
        'invalid_grant_type' => 'не верный grant_type',
        'invalid_client' => 'не верный client_id',
        'not_exist_client' => 'приложение не существует в системе',
        'invalid_uri' => 'не задан redirect_uri',
        'invalid_request_type' => 'не верный тип запроса',
        'invalid_auth_hash' => 'не указана контрольная сумма',
        'not_equal_auth_hash' => 'не верная контрольная суммы',
        'not_save_code' => 'не удалось создать код подтверждения',
        'auth_user_cancel' => 'отменено пользователем',
        'invalid_code' => 'не задан код подтверждения',
        'invalid_expires_code' => 'код не актуальный',
        'not_exist_code' => 'код не существует в системе',
        'invalid_client_secret' => 'не указан пароль приложения',
        'invalid_uid' => 'не верный пользователь',
        'is_guest' => 'пользователь не авторизирован',
        'not_save_token' => 'не удалось создать токен',
        'invalid_username' => 'не верный username',    
        'invalid_password' => 'не верный password',
        'not_validate_username' => 'не верное имя пользователя',
        'not_validate_password' => 'не верный пароль пользователя',
    ];

    public function load($params = [])
    {
        $this->clientId = $params['client_id'];
        $this->clientSecret = $params['client_secret'];
        $this->redirectUri = $params['redirect_uri'];
        $this->code = $params['code'];
        $this->state = $params['state'];
        
        if($params['display']) {
            $this->display = $params['display'];
        } 
        if($params['state']) {
            $this->state = $params['state'];
        } 
        if($params['version']) {
            $this->display = $params['version'];
        } 
    }
    
    public function buildAuthUrl($url = null, $params = [])
    {
        $defaultParams = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'display' => $this->display,
        ];

        if ($this->validateAuthState) {
            $authState = $this->generateAuthState();
            $defaultParams['state'] = $authState;
        }
        
        $defaultParams['h'] = B2bOauth2::generateAuthHash([
            $this->clientId,
            $this->redirectUri,
            $authState
        ]);

        return $this->composeUrl($url, array_merge($defaultParams, $params));
    }
    
    public function fetchAccessToken($authCode, $params = [])
    {
        $token = json_encode(['dffasd-32423-sadasdasdsadasddffasd-32423-sadasdasdsadasddffasd-32423-sadasdasdsadasd']); $password = '1234';
        $salt = substr(md5(mt_rand(), true), 8);

    $key = md5($password . $salt, true);
    $iv  = md5($key . $password . $salt, true);

    $ct = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $token, MCRYPT_MODE_CBC, $iv);

    return base64_encode('Salted__' . $salt . $ct);
        return $token;
    }

    protected function generateAuthState()
    {
        $baseString = get_class($this) . '-' . time();
        if (Yii::$app->has('session')) {
            $baseString .= '-' . Yii::$app->session->getId();
        }
        return hash('sha256', uniqid($baseString, true));
    }
    
    public function generateAuthHash($params = [])
    {
        return hash('sha256', implode('', $params));
    }

    public function composeUrl($url, $params = [])
    {
        if (!empty($params)) {
            if (strpos($url, '?') === false) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $url .= http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }
        return $url;
    }
}
