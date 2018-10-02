<?php

namespace backend\modules\AccesPanel\models;

use Yii;

/**
 * This is the model class for table "b2b_OauthCodes".
 *
 * @property string $authorization_code
 * @property string $client_id
 * @property integer $user_id
 * @property string $redirect_uri
 * @property string $expires
 * @property string $scope
 *
 * @property OauthClients $client
 */
class Codes extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_OauthCodes';
    }
    
    const EXPIRES_LIMIT = 10;
    const USER_ENCRYPT = 'encrypt_user_code_key';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code', 'clientId', 'redirectUri'], 'required'],
            [['userId'], 'integer'],
            [['expires'], 'safe'],
            [['code'], 'string', 'max' => 40],
            [['clientId'], 'string', 'max' => 32],
            [['redirectUri'], 'string', 'max' => 1000],
            [['scope'], 'string', 'max' => 2000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'code' => Yii::t('app', 'Authorization Code'),
            'clientId' => Yii::t('app', 'Client ID'),
            'userId' => Yii::t('app', 'User ID'),
            'redirectUri' => Yii::t('app', 'Redirect Uri'),
            'expires' => Yii::t('app', 'Expires'),
            'scope' => Yii::t('app', 'Scope'),
        ];
    }
    
    public function oneExpires($params = [])
    {
        return Codes::find()->where([
            'userId' => Yii::$app->user->id,
            'redirectUri' => $params['redirect_uri'],
            'clientId' => $params['client_id'],
        ])->andWhere(['<=', 'expires', date('Y-m-d H:i:s')])->one();
    }
    
    public function checkExpires($code = null)
    {
        return Codes::find()->where(['code' => $code])->andWhere(['<=', 'expires', date('Y-m-d H:i:s')])->count();
    }
    
    public function existByParams($params = [])
    {
        return Codes::find()->where($params)->count();
    }
    
    public function userIdByParams($params = [])
    {
        return Codes::find()->select(['userId'])->where($params)->one()->userId;
    }
    
    private function generateAuthCode()
    {
        $model->code = md5(uniqid(rand(), true));
    }
    
    public function add($data = [])
    {
        $app = App::oneWhithPermis(['id' => $data['client_id']]);
        $permissios = [];
        
        foreach($app->permissios as $el)
        {
            $permissios[] = $el->permission->permissionName;
        }
        
        $model = new Codes($data);
        $model->expires = date('Y-m-d H:i:s', strtotime('+' . Codes::EXPIRES_LIMIT . ' minutes'));
        $model->scope = implode(',', $permissios);
        $model->generateAuthCode();
        
        if ($model->save()) {
            return $model;
        } 
        
        return false;
    }
}
