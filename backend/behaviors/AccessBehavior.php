<?php
/**
 * AccessBehavior for Yii2
 *
 * @author Elle <elleuz@gmail.com>
 * @version 0.1
 * @package AccessBehavior for Yii2
 *
 */
namespace backend\behaviors;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\di\Instance;
use yii\web\User;
use yii\web\ForbiddenHttpException;
use yii\web\Controller;

class AccessBehavior extends AttributeBehavior {

    public $rules=[];
    
    public $adminRules=[
        'allow' => true,
        'roles' => ['admin'],
    ];
    
    public $denyCallback;

    private $_rules = [];

    public function events(){
        if(!$this->rules){
            $this->rules=[
                Yii::$app->controller->id=>[
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'actions'=>['*']
                    ]
                ]
            ];    
        }
        
        $this->rules[Yii::$app->controller->id][]=$this->adminRules;
        
        return [Controller::EVENT_BEFORE_ACTION => 'interception'];
    }

    public function interception($event)
    {
        /*
        if(!isset( Yii::$app->i18n->translations['db_rbac'])){
            Yii::$app->i18n->translations['db_rbac'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'ru-Ru',
                'basePath' => '@developeruz/db_rbac/messages',
            ];
        }
        */

        $route = Yii::$app->getRequest()->resolve();
        
        $this->addAction();

        //Проверяем права по конфигу
        $this->createRule();
        $user = Instance::ensure(Yii::$app->user, User::className());
        $request = Yii::$app->getRequest();
        $action = $event->action;

        if(!$this->cheсkByRule($action, $user, $request))
        {
            //И по AuthManager
            if(!$this->checkPermission($route))
                throw new ForbiddenHttpException(Yii::t('db_rbac','Недостаточно прав'));
        }
    }
    
    protected function addAction(){
            $action=Yii::$app->controller->id.'/'.Yii::$app->controller->action->id;
            $role=Yii::$app->authManager->getRole('admin');
            
            if(!Yii::$app->authManager->getPermission(Yii::$app->controller->id)){
                $permit = Yii::$app->authManager->createPermission(Yii::$app->controller->id);
                $permit->description = '';
                Yii::$app->authManager->add($permit);
    
                Yii::$app->authManager->addChild($role, $permit);
            }
    
            if(!Yii::$app->authManager->getPermission($action)){
                $permit = Yii::$app->authManager->createPermission($action);
                $permit->description = '';
                Yii::$app->authManager->add($permit);
                
                Yii::$app->authManager->addChild($role, $permit);
            }     
           
    }

    protected function createRule()
    { 
        foreach($this->rules as $controller => $rule)
        {
            foreach ($rule as $singleRule) {
                if (is_array($singleRule)) {
                    $option = [
                        'controllers' => [$controller],
                        'class' => 'yii\filters\AccessRule'
                    ];
                    $this->_rules[] = Yii::createObject(array_merge($option, $singleRule));
                }
            }
        }
    }

    protected function cheсkByRule($action, $user, $request)
    {
        foreach ($this->_rules as $rule) {
            if ($allow = $rule->allows($action, $user, $request))   return true;
            elseif ($allow === false) { 
                if (isset($rule->denyCallback)) {
                    call_user_func($rule->denyCallback, $rule, $action);
                } elseif (isset($this->denyCallback)) {
                    call_user_func($this->denyCallback, $rule, $action);
                } else {
                    $this->denyAccess($user);
                }
                return false;
            }
        }
        
        if (isset($this->denyCallback)) {
            call_user_func($this->denyCallback, null, $action);
        }
        
        return false;
    }

    protected function checkPermission($route)
    {
        //$route[0] - is the route, $route[1] - is the associated parameters

        $routePathTmp = explode('/', $route[0]);
        $routeVariant = array_shift($routePathTmp);
        if(Yii::$app->user->can($routeVariant, $route[1]))
            return true;

        foreach($routePathTmp as $routePart)
        {
            $routeVariant .= '/'.$routePart;
            if(Yii::$app->user->can($routeVariant, $route[1]))
                return true;
        }

        return false;
    }
}