<?php

namespace backend\models;

use Yii;
use common\models\Company;
use common\models\Registration;
use common\models\User;
use common\models\Address;
use common\models\CompanyDivision;

use common\components\SMTP_validateEmail;
use common\behaviors\ActionLogsBehavior;
use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "b2b_RegistrationCustom".
 *
 * @property integer $id
 * @property integer $fromExcel
 * @property string $companyName
 * @property integer $companyId
 * @property string $userName
 * @property integer $userId
 * @property string $log
 * @property integer $status
 * @property string $dateAdd
 */
class RegistrationCustom extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'b2b_RegistrationCustom';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fromExcel', 'companyId', 'userId', 'status'], 'integer'],
            [['log', 'company', 'inn'], 'required'],
            [['log'], 'string'],
            [['dateAdd'], 'safe'],
            [['company', 'user'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'fromExcel' => Yii::t('app', 'Добавлено из Excel'),
            'company' => Yii::t('app', 'Название компании'),
            'companyId' => Yii::t('app', 'ID компании'),
            'inn' => Yii::t('app', 'INN'),
            'user' => Yii::t('app', 'Пользователь'),
            'userId' => Yii::t('app', 'Пользователь ID'),
            'log' => Yii::t('app', 'Лог'),
            'status' => Yii::t('app', 'Статус'),
            'dateAdd' => Yii::t('app', 'Добавлено'),
        ];
    }
    
    public function behaviors()
    {
        return [
            'ActionLogsBehavior' => [
                'class' => ActionLogsBehavior::className(),
            ],
        ];
    }
    
    public $users = [];
    public $phone;
    public $email;
    public $okpds = [];
    
    public $errors = [];
    public $success = [];
    
    public function addLog($log = null)
    {
        $this->log = $log;
        $this->save();
    }
    
    public function companyExist()
    {
        return Company::find()->where(['INN' => $this->inn])->count();
    }
    
    public function userExistByEmail()
    {
        return User::find()->where(['userName' => $this->email])->count();
    }
    
    public function checkEmail($email = null)
    {
        return true;
        if($email)
        {
            $SMTP_Validator = new SMTP_validateEmail();
            
            $validate = $SMTP_Validator->validate([$email]);
            if(!$validate || !$validate[$email])
            {
                return false;
            } 
            
            return true;   
        }
        
        return false;
    }
    
    public function listFromExcel()
    {
        $model = RegistrationCustom::find()->select('company')->where(['fromExcel' => 1])->asArray()->All();        
        return ArrayHelper::getColumn($model, 'company');
    }
    
    public function listCompanyByInn($inn = [])
    {
        $model = Company::find()->where(['INN' => $inn])->asArray()->All();        
        return ArrayHelper::map($model, 'INN', 'id');
    }
    
    public function registrationFromExcel($data = [])
    {
        if($data)
        {
            $errorLog = [];
            $successLog = [];
            
            $count = 30;
            $i = 0;
            
            $listFromExcel = self::listFromExcel();
            
            $innLIst = ArrayHelper::getColumn($data, 'inn');
            $listCompany = self::listCompanyByInn($innLIst);
            
            foreach($data as $el)
            {
                if($el['okpds'] && isset($listCompany[$el['inn']]))
                { 
                    $companyId = $listCompany[$el['inn']];
                    foreach($el['okpds'] as $el){
                        CompanyDivision::addItem($companyId, $el, 1);
                    } 
                }
            }
            
            var_dump('ok');die;
            return 0;
            
            foreach($data as $el)
            {
                if(!in_array($el['company'], $listFromExcel))
                {
                    $model = new self($el);
                    $model->fromExcel = 1;
                    
                    /**
                     * фикс нескольких емаилов одного пользователя
                     */
                    if($model->users)
                    {
                        foreach($model->users as &$user)
                        {
                            if($user['email'])
                            {
                                $emails = self::multiexplode([',', ';'], $user['email']);
                                if($emails)
                                {
                                    $user['email'] = trim($emails[0]);
                                    unset($emails[0]);
                                    
                                    if($emails)
                                    {
                                        foreach($emails as $email)
                                        {
                                            $model->users[] = [
                                                'name' => $user['name'],
                                                'phone' => null,
                                                'email' => trim($email)
                                            ];    
                                        }
                                        
                                    }
                                }
                            }
                        }
                    }

                    $model->registration();
                    
                    if($model->errors)
                    {
                        $errorLog = array_merge($errorLog, $model->errors);
                        $model->addLog(implode('<br/>', $model->errors));
                    }
                    if($model->success)
                    {
                        $successLog = array_merge($successLog, $model->success);
                        $model->addLog(implode('<br/>', $model->success));
                    }   
                    
                    $i ++ ;
                    if($i == $count)    break;
                }
            }
        }
        
        if($errorLog)
        {
            Yii::$app->session->setFlash('error', implode('<br/>', $errorLog)); 
        }
        if($successLog)
        {
            Yii::$app->session->setFlash('success', implode('<br/>', $successLog)); 
        }
    }
    
    
    public function registration(){
        if($this->users && $this->company)
        {
            if($this->inn && $this->companyExist())
            {
                $this->errors[] = 'Компания ' . $this->company . ' с ИНН ' . $this->inn . ' уже существует';
                return false;
            }
            
            $user = [];
            
            foreach($this->users as $key => $u){
                if($u['email'])
                {
                    $u['phones'] = [];
                    if($u['phone']){
                        $u['phones'] = self::multiexplode([',', '.', '|', ':', ';'], $u['phone']);
                    }
                    
                    if(!$this->checkEmail($u['email']))
                    {
                        $this->errors[] = 'Не корректный email ' . $u['email'] . ' (' . $this->company . ')';
                        unset($this->users[$key]);
                    }
                    
                    else if($this->userExistByEmail($u['email']))
                    {
                        $this->errors[] = 'Пользователь с email ' . $u['email'] . ' уже существует' . ' (' . $this->company . ')';
                        unset($this->users[$key]);
                    }
                    else if(!$user)
                    {
                        $user = $u;
                        unset($this->users[$key]);
                    }    
                }
                else
                {
                    unset($this->users[$key]);
                }
                
            }

            if(!$user)
            {
                $this->errors[] = 'Не удалось добавить компанию ' . $this->company . '. Нет ни одного валидного email.';
                return false;
            }
            
            $this->user = $user['name'];
            
            $dadata = json_decode(Company::searchByDadata($this->inn), true);
            if(!$dadata["suggestions"])
            {
                $this->errors[] = 'Не удалось найти компанию ' . $this->company . ' в dadata';
                return false;
            }
       
            if($dadata["suggestions"] && !$this->errors)
            {
                $company = $dadata["suggestions"][0];
                $companyData = $company['data'];
    
                if($this->inn && $this->inn != $companyData['inn'])
                {
                    $this->errors[] = 'Не удалось добавить компанию ' . $this->company . '. Не совпадают ИНН ' . $this->inn . ' с сервиса dadata ' . $companyData['inn'];
                    return false;
                }
                
                
                $addressData = $companyData['address']['data'];
                if(!$addressData)
                {
                    $dadata = json_decode(Address::searchByDadata($companyData['address']['value']), true);
                    $addressData = $dadata["suggestions"][0]['data'];
                }
    
                $userName = explode(' ', $this->user);
                $post = 'Генеральный директор';
                
                if(strstr($companyData['management']['name'], $userName[0]))
                {
                    $userName = explode(' ', $companyData['management']['name']);
                    $post = $companyData['management']['post'];
                }

                $model = new Registration([
                    'companyINN' => $companyData['inn'],
                    'companKPP' => $companyData['kpp'] ? $companyData['kpp'] : 0,
                    'companOGRNIP' => $companyData['ogrn'],
                    'companyType' => $companyData['type'] == 'LEGAL' ? 1 : 2,
                    'companyCountry' => 1,
                    'companyName' => $companyData['name']['short_with_opf'] ? $companyData['name']['short_with_opf'] : $this->company,
                    'companyNameShort' => $companyData['name']['full_with_opf'] ? $companyData['name']['full_with_opf'] : $this->company,
                    'companyOkpo' => $companyData['okpo'] ? $companyData['okpo'] : 0,
                    'companyMeetsBusiness' => 0,
                    
                    'userName' => $user['email'],
                    'lastName' => isset($userName[0]) ? $userName[0] : null,
                    'name' => isset($userName[1]) ? $userName[1] : null,
                    'patronymic' => isset($userName[2]) ? $userName[2] : null,
                    'userPost' => $post,
                    'phone' => $user['phones'] ? $user['phones'] : [],
                    'phoneIsCell' => 0,
                    'userIsDirector' => 1,
                    'userIsAccountant' => 1,
                    
                    'companyAddressKladr' => $addressData['kladr_id'],
                    'companyAddressText' => $companyData['address']['value'],
                    'companyAddress2IsCoincidence' => 1,
                    'companyAddress3IsCoincidence' => 1,
                    'companyAddressCountry' => 643,
                    'companyAddressPostIndex' => $addressData['postal_code'],
                    
                    'agreeTerms' => 1,
                    'agreeOffer' => 1
                ]);
                
                if($user = $model->registrate())
                {
                    $this->success[] = 'Компания ' . $this->company . ' успешно добавлена';
                    $this->status = 1;
                    $this->userId = $user->id;
                    $this->companyId = $user->companyId;
                    
                    if($this->users)
                    {                        
                        foreach($this->users as $el)
                        {
                            if($el['email'])
                            {
                                $userName = explode(' ', $el['name']);
                                                                
                                $modelUser = new User([
                                    'userName' => $el['email'],
                                    'companyId' => $user->companyId,
                                    'lastName' => isset($userName[0]) ? $userName[0] : null,
                                    'name' => isset($userName[1]) ? $userName[1] : null,
                                    'patronymic' => isset($userName[2]) ? $userName[2] : null,
                                    'phone' => $el['phones'] ? $el['phones'] : [],
                                    'phoneIsCell' => 0,
                                ]);
			
                				$userPassword = Yii::$app->security->generateRandomString(8);
                				$modelUser->setPassword($userPassword);
                				if($modelUser->save()){
                				    $this->success[] = 'Пользователь ' . $el['email'] . ' добавлен';
                				}
                            }
                        }
                    }
                }
                else
                {
                    $this->errors = $model->getErrors();
                }
            }
            else
            {
                $this->errors[] = 'Не удалось добавить компанию ' . $this->company . '. Не найдены данные в dadata';
                return false;
            }
        }
        else
        {
            $this->errors[] = 'В компании должен быть хотя бы один пользователь и название компании должно быть заполнено';
            return false;
        }
    }
    
    private function multiexplode($delimiters = [], $string = null) 
    {
    
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        foreach($launch as &$el)    trim($el);
        return  $launch;
    }
}
