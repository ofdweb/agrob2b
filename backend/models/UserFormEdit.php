<?php
namespace backend\models;

use common\models\User;
use common\models\Company;
use common\components\THelper;
use common\behaviors\ActionLogsBehavior;
use yii\base\Model;
use Yii;

/**
 * Signup form
 */
class UserFormEdit extends Model
{
    public $userName;
    public $name;
    public $lastName;
    public $patronymic;
    public $userPost;
    public $phone;
    public $password;
	public $id;
	public $isDirector;
    public $dateCreate;
    public $fax;
    public $isNewRecord;
    public $companyId;
    public $company;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			[['name', 'userPost', 'lastName', 'patronymic', 'phone','fax'], 'string'],
			[['id', 'isDirector'], 'integer'],
			
			
            ['userName', 'filter', 'filter' => 'trim'],
            ['userName', 'email'],
            //['userName', 'required'],
            //['userName', 'unique', 'targetClass' => '\common\models\User', 'message' => Yii::t('model', 'Этот почтовый адрес уже занят.')],

            ['password', 'string', 'min' => 6],
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
    
    
    public function load($id=''){
        $model = ($id)?User::FindOne($id):User::find();
        return $model;
    }
    
    public function oneByPhone($phone=null){
        if($phone) return User::find()->where(['in','phone',$phone])->one();
    }
    
    public function itemListPhone($phone=null){
        $model= User::find()->all();
        $result=[];
        foreach($model as $el) $result[$el->phone]=$el->lastName.' '.$el->name.($el->phone?' ('.$el->phone.')':'');
        return $result;
    }
    
    public function create(){
        $model = new UserFormEdit();
        return $model;
    }
    
    public function delete($id=''){
        if(User::deleteall(['id'=>$id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
    }

	public function update($id='',$attr='')
	{
        $model=new UserFormEdit();
        if($id && is_numeric($id)){
			$user=User::FindOne($id);
            $model->attributes=$user->attributes;
		}
        
        if($model->companyId!=$attr["companyId"] && $_POST['block']==1){
           Company::updateAll(['forDelete' => 1],['id'=>$model->companyId]);
        }
            
        $model->attributes=$attr;

        if($model->validate()){
            foreach($attr as $key=>$el) $user->$key=$el;
            $user->generateAuthKey();
            if($user->save())   Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
            else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
        }
        else Yii::$app->session->setFlash('error', 'Заполните все поля');

        return $model;
    }
}
