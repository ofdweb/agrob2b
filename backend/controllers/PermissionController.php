<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use common\behaviors\AccessBehavior;
use yii\web\BadRequestHttpException;
use common\models\rbac\Role;
use common\models\rbac\Permission;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\validators\RegularExpressionValidator;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use common\models\Company;

use common\models\User;

class PermissionController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
				'class' => AccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'roles' => ['admin'],
					],	
				],	
			]
        ];
    }
    
    protected $error;
    protected $pattern4Role = '/^[a-zA-Z0-9-_]+$/';

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionRole($companyId = null)
    {
        $dataProvider = new ArrayDataProvider([
            'allModels' => Yii::$app->authUserManager->getRoles(),
            'sort' => [
                'attributes' => ['name', 'description'],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        
        return $this->render('role', compact('dataProvider')); 
    }

    public function actionAddRole()
    {
        if (Yii::$app->request->isPost) {
            $role = Yii::$app->authUserManager->createRole(Yii::$app->request->post('name'));
            
            if ($role->validate() && $role->isUnique()) {
                $role->setAttributes(Yii::$app->request->post());
                
                Yii::$app->authUserManager->add($role);
                Yii::$app->authUserManager->setPermissions(Yii::$app->request->post('permissions', []), $role);
            }
 
            $redirect = Yii::$app->request->post('save') !== null ? ['role'] : ['add-role'];
            return $this->redirect(Url::toRoute($redirect));
        }

        $permissions = ArrayHelper::map(Yii::$app->authUserManager->getPermissions(), 'name', 'description');
        
        return $this->render(
            'edit-role',
            [
                'permissions' => $permissions,
                'error' => $role->error
            ]
        );
    }

    public function actionUpdateRole($name)
    {
        $role = Yii::$app->authUserManager->getRole($name);

        if ($role instanceof Role) {
            if (Yii::$app->request->post('name')) {
                $role->setAttributes(Yii::$app->request->post());

                if ($role->validate()) {
                    //Yii::$app->authUserManager->removeChildren($name);
                    Yii::$app->authUserManager->update($name, $role);
                    //Yii::$app->authUserManager->setPermissions(Yii::$app->request->post('permissions', []), $role);
                    
                    $redirect = Yii::$app->request->post('save') !== null ? ['role'] : ['add-role'];
                    return $this->redirect(Url::toRoute($redirect));
                }    
            }
            

            $permissions = ArrayHelper::map(Yii::$app->authUserManager->getPermissions(), 'name', 'description');
            $role_permit = array_keys(Yii::$app->authUserManager->getPermissionsByRole($name));

            return $this->render(
                'edit-role',
                [
                    'role' => $role,
                    'permissions' => $permissions,
                    'role_permit' => $role_permit,
                    'error' => $role->error
                ]
            );
        } else {
            throw new BadRequestHttpException(Yii::t('db_rbac', 'Страница не найдена'));
        }
    }

    public function actionDeleteRole($name)
    {
        $role = Yii::$app->authUserManager->getRole($name);
        if ($role) {
            Yii::$app->authUserManager->removeChildren($role->name);
            Yii::$app->authUserManager->remove($role);
        }
        return $this->redirect(Url::toRoute(['role']));
    }


    public function actionPermission($name = null)
    {
        $permis = Yii::$app->authUserManager->getPermissionsSort();
        $rules = ArrayHelper::map(Yii::$app->authUserManager->getRules(), 'name', 'description');  
        $companyList = ArrayHelper::map(Company::itemList(), 'id', 'nameShort');      

        $dataProvider = new ArrayDataProvider([
              'allModels' => $permis,
              'sort' => [
                'attributes' => ['description', 'name'],
              ],
              'pagination' => [
                'pageSize' => count($permis),
              ],
        ]);
        
        return $this->render('permission', compact('dataProvider', 'rules', 'companyList'));
    }
    

    public function actionAddPermission()
    {
        if (Yii::$app->request->isPost) {
            $permit = Yii::$app->authUserManager->createPermission(Yii::$app->request->post('name'));
            
            if ($permit->validate() && $permit->isUnique()) {
                $permit->setAttributes(Yii::$app->request->post());

                Yii::$app->authUserManager->add($permit);
                
                $parentPerm = Yii::$app->authUserManager->getPermission(Yii::$app->request->post('parent', null));
                Yii::$app->authUserManager->changeChild($parentPerm, $permit);   
            }

            $redirect = Yii::$app->request->post('save') !== null ? ['permission'] : ['add-permission'];
            return $this->redirect(Url::toRoute($redirect));
        }
        
        $permissions = ArrayHelper::map(Yii::$app->authUserManager->getPermissions(), 'name', 'description');
        $rules = ArrayHelper::map(Yii::$app->authUserManager->getRules(), 'name', 'description');
        $companyList = ArrayHelper::map(Company::itemList(), 'id', 'nameShort');
        
        return $this->render('edit-permission', ['error' => $permit->error, 'permissions' => $permissions, 'rules' => $rules, 'companyList' => $companyList]);
    }

    public function actionUpdatePermission($name)
    { 
        $permit = Yii::$app->authUserManager->getPermission($name);

        if ($permit instanceof Permission) {
            
            if (Yii::$app->request->post('name')) {
                $permit->setAttributes(Yii::$app->request->post());

                if ($permit->validate()) {
                    Yii::$app->authUserManager->update($name, $permit);

                    $parentPerm = Yii::$app->authUserManager->getPermission(Yii::$app->request->post('parent', null));
                    Yii::$app->authUserManager->changeChild($parentPerm, $permit);

                    $redirect = Yii::$app->request->post('save') !== null ? ['permission'] : ['add-permission'];
                    return $this->redirect(Url::toRoute($redirect));
                }    
            }
            
            $permissions = ArrayHelper::map(Yii::$app->authUserManager->getPermissions(), 'name', 'description');
            $rules = ArrayHelper::map(Yii::$app->authUserManager->getRules(), 'name', 'description');
            $companyList = ArrayHelper::map(Company::itemList(), 'id', 'nameShort');
     
            return $this->render('edit-permission', ['permit' => $permit, 'error' => $permit->error, 'permissions' => $permissions, 'rules' => $rules, 'companyList' => $companyList]);
            
        } else throw new BadRequestHttpException(Yii::t('db_rbac','Страница не найдена'));
    }

    public function actionDeletePermission($name)
    {
        $permit = Yii::$app->authUserManager->getPermission($name);
        if ($permit) {
            Yii::$app->authUserManager->remove($permit);
        }
            
        return $this->redirect(Url::toRoute(['permission']));
    }
    
    public function actionRules()
    {

        $dataProvider = new ArrayDataProvider([
              'allModels' =>  Yii::$app->authUserManager->getRules(),
              'sort' => [
                'attributes' => ['name'],
              ],
        ]);
        
        return $this->render('rules', compact('dataProvider'));
    }

    public function actionEditRule($name = null)
    {
        $rule = Yii::$app->authUserManager->getRule($name);

        if (Yii::$app->request->isPost) {
            $modelClass = Yii::$app->request->post('namespace');
            
            if ($modelClass && class_exists($modelClass)) {
                if ($rule) {
                    Yii::$app->authUserManager->remove($rule);
                }
                
                $rule = new $modelClass;
                Yii::$app->authUserManager->add($rule);
            } else {
                throw new BadRequestHttpException(Yii::t('db_rbac','Ошибка или класс не найден'));
            }

            $redirect = Yii::$app->request->post('save') !== null ? ['rules'] : ['edit-rule'];
            return $this->redirect(Url::toRoute($redirect));
        }

        return $this->render('edit-rule', compact('rule'));
    }

}