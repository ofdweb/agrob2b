<?php

namespace backend\modules\accespanel\models;

use Yii;
use common\components\Encrypt;
use backend\modules\AccesPanel\models\App;

class Tokens extends \yii\db\ActiveRecord {
	
	public static function tableName() {
        return 'b2b_OauthTokens';
    }
    
    const STATUS_ACTIVE = 1;
    const STATUS_UNACTIVE = 0;
    const STATUS_CANCELED = 2;
    
    public static $statusList = [
        Tokens::STATUS_ACTIVE => 'Активный',
        Tokens::STATUS_UNACTIVE => 'Не активный',
        Tokens::STATUS_CANCELED => 'Отозван',
    ];

    const ENCRYPT = 'encrypt_token_key';
	
	public function rules() {
        return [
            [['idUser', 'idApp'], 'integer'],
            [['token', 'refreshToken', 'permissios'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 40],
            [['dateCreate', 'dateTo'], 'safe'],
            [['status'], 'integer'],
        ];
    }
	
	public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'token' => 'Токен',
            'code' => 'Код доступа',
            'refreshToken' => 'Токен восстановления',
            'idApp' => 'Приложение',
            'idUser' => 'Пользователь',
			'dateCreate' => 'Дата создания',
			'dateTo' => 'Дата окончания действия',
            'status' => 'Статус'
        ];
    }
    
    public function fetchAccessToken($data = []) 
    {
        $modelApp = App::oneWhithPermis(['id' => $this->idApp]);

        foreach($modelApp->permissios as $el) {
            $this->permissios[] = $el->permission->permissionName;
        }
        
        $this->dateTo = $modelApp->dateTo;
        $this->fetchToken();
    }
    
    public function refreshAccessToken($refreshToken = null)
    {
        $token = Tokens::find()->where(['refreshToken' => $refreshToken])->orderBy(['id' => "SORT_DESC"])->one();
        
        if ($token) {
            $modelApp = App::oneWhithPermis(['id' => $token->idApp]);

            foreach($modelApp->permissios as $el) {
                $token->permissios[] = $el->permission->permissionName;
            }
            
            $refresh = new Tokens([
                'dateTo' => $modelApp->dateTo,
                'idUser' => $token->idUser,
                'idApp' => $token->idApp
            ]);

            $refresh->fetchToken();
            
            $refresh->refreshToken = $refreshToken;
            $refresh->save(); 
            
            return $refresh;
        }
        
        return false;
    }
    
    
    public function oneByParams($params = [])
    {
        return Tokens::find()->where($params)->one();
    }
    
    public function fetchToken()
    {
        $this->dateTo = date('Y-m-d H:i:s', strtotime('+' . $this->dateTo . ' days'));
        $this->permissios = implode(',', $this->permissios);
        $this->status = Tokens::STATUS_ACTIVE;
        $this->generateRefreshToken();
        $this->generateToken();
    }
    
    private function generateToken()
    {
        $data = [
            'uid' => $this->idUser,
            'app' => $this->idApp,
            'exp' => $this->dateTo,
        ];
        
        $jsonData = json_encode($data);
        $security = new Encrypt(Tokens::ENCRYPT);
        $this->token = $security->encode($jsonData); 

        $this->save(); 
    }
    
    private function generateRefreshToken()
    {
        $this->refreshToken = md5(uniqid(rand(), true));
    }
    
    public function userToken($token = null)
    {
        return Tokens::find()->select(['idUser', 'dateTo'])->where(['token' => $token, 'status' => Tokens::STATUS_ACTIVE])->one();
    }
    
    public function checkExpire()
    {
        if ($this->dateTo < date('Y-m-d H:i:s')) {
            $this->setStatus(Tokens::STATUS_UNACTIVE);
            return false;
        }
        return true;
    }
    
    public function cancel()
    {
        $this->setStatus(self::STATUS_CANCELED);
    }
    
    public function setStatus($status = null)
    {
        $this->status = $status;
        $this->save();
    }
	
}