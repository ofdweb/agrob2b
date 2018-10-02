<?php
/**
 * AccessController for Yii2
 *
 * @author Elle <elleuz@gmail.com>
 * @version 0.1
 * @package AccessController for Yii2
 *
 */
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\rbac\Role;
use yii\rbac\Permission;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\validators\RegularExpressionValidator;
use yii\data\ArrayDataProvider;

class AccessController extends Controller
{
    protected $error;
    protected $pattern4Role = '/^[a-zA-Z0-9_-]+$/';
    protected $pattern4Permission = '/^[a-zA-Z0-9_\/-]+$/';
    
    public function behaviors()
    {
        return [
			'access' => [
				'class' => \backend\behaviors\AccessBehavior::className(),
			],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionRole()
    {
        return $this->render('role');
    }

    public function actionAddRole()
    {
        $roles['new']='';
        $dataProvider=$this->columnDataProvider($roles);
        
        if (Yii::$app->request->post('name')
            && $this->validate(Yii::$app->request->post('name'), $this->pattern4Role)
            && $this->isUnique(Yii::$app->request->post('name'), 'role')
        ) {
            $role = Yii::$app->authManager->createRole(Yii::$app->request->post('name'));
            $role->description = Yii::$app->request->post('description');
            Yii::$app->authManager->add($role);

            $permissions=Yii::$app->request->post('permissions', []);

            Yii::$app->authManager->removeChildren($role);
            if(isset($permissions['role_new']))   $this->setPermissions($permissions['role_new'], $role);
            
            if($role->name){
                return $this->redirect(Url::toRoute([
                    'update-role',
                    'name' => $role->name,
                ]));    
            }
            
        }

        $permissions = ArrayHelper::map(Yii::$app->authManager->getPermissions(), 'name', 'description');
        return $this->render(
            'updateRole',
            [
                'permissions' => $permissions,
                'error' => $this->error,
                'dataProvider'=>$dataProvider,
                'roles'=>$roles,
            ]
        );
    }

    public function actionUpdateRole($name)
    {
        $role = Yii::$app->authManager->getRole($name);
        
        $roles[$role->name]=$role->description;

        $dataProvider=$this->columnDataProvider($roles);

        $permissions = ArrayHelper::map(Yii::$app->authManager->getPermissions(), 'name', 'description');
        $role_permit = array_keys(Yii::$app->authManager->getPermissionsByRole($name));

        if ($role instanceof Role) {
            if (Yii::$app->request->post('name')
                && $this->validate(Yii::$app->request->post('name'), $this->pattern4Role)
            ) {
                if (Yii::$app->request->post('name') != $name && !$this->isUnique(Yii::$app->request->post('name'), 'role')) {
                    return $this->render(
                        'updateRole',
                        [
                            'role' => $role,
                            'permissions' => $permissions,
                            'role_permit' => $role_permit,
                            'dataProvider'=>$dataProvider,
                            'roles'=>$roles,
                            'error' => $this->error
                        ]
                    );
                }
                
                $post=Yii::$app->request->post();
                
                if(isset($post['clone'])){
                    $suf='_'.rand();
                    $role = Yii::$app->authManager->createRole(Yii::$app->request->post('name').$suf);
                    $role->description = Yii::$app->request->post('description').$suf;
                    Yii::$app->authManager->add($role);
                }
                else{
                    $role = $this->setAttribute($role, Yii::$app->request->post());
                    Yii::$app->authManager->update($name, $role);
                }

                $permissions=Yii::$app->request->post('permissions', []);
                foreach($roles as $key=>$el){
                    Yii::$app->authManager->removeChildren($role);
                    if($role && isset($permissions['role_'.$key]))   $this->setPermissions($permissions['role_'.$key], $role);
                }
                
                return $this->redirect(Url::toRoute([
                    'update-role',
                    'name' => $role->name
                ]));
            }

            return $this->render(
                'updateRole',
                [
                    'role' => $role,
                    'permissions' => $permissions,
                    'role_permit' => $role_permit,
                    'dataProvider'=>$dataProvider,
                    'roles'=>$roles,
                    'error' => $this->error
                ]
            );
        } else {
            throw new BadRequestHttpException(Yii::t('db_rbac', 'Страница не найдена'));
        }
    }

    public function actionDeleteRole($name)
    {
        $role = Yii::$app->authManager->getRole($name);
        if ($role) {
            Yii::$app->authManager->removeChildren($role);
            Yii::$app->authManager->remove($role);
        }
        return $this->redirect(Url::toRoute(['role']));
    }
    
    private function columnDataProvider($roles=[]){
        $permission=(array)Yii::$app->authManager->getPermissions();
        
        foreach($roles as $key=>$el)  $role_permit[$key]=Yii::$app->authManager->getPermissionsByRole($key);

        $permis=[];
        foreach($permission as $key=>$el){
            $permis[$key]=[
                'type'=>$el->type,
                'name'=>$el->name,
                'description'=>$el->description,
            ];
            if($roles){
                foreach($roles as $k=>$e){
                    $permis[$key]['role_'.$k]=isset($role_permit[$k][$el->name])?$el->name:0;
                }   
            }
        }    

        $dataProvider = new ArrayDataProvider([
              'allModels' =>$permis,
              'sort' => [
                  'attributes' => ['name'],
              ],
              'pagination' => [
                  'pageSize' => count($permis),
              ],
        ]);
        
        return $dataProvider;
    }


    public function actionPermission($name=null)
    {
        $roles = ArrayHelper::map(Yii::$app->authManager->getRoles($name), 'name', 'description');
        
        if(Yii::$app->request->post()){ 
            $permissions=Yii::$app->request->post('permissions', []);
            
            foreach($roles as $key=>$el){
                $role=Yii::$app->authManager->getRole($key);
                Yii::$app->authManager->removeChildren($role);
                if(isset($permissions['role_'.$key]))   $this->setPermissions($permissions['role_'.$key], $role);
            }
            
            $descriptions=Yii::$app->request->post('description', []);
            foreach($descriptions as $key=>$el){
                if($el){
                    $permit = Yii::$app->authManager->getPermission($key);
                    $permit->description = $el;
                    Yii::$app->authManager->update($key, $permit);    
                }
            }
        }
        
        $dataProvider=$this->columnDataProvider($roles);

        return $this->render('permission',[
            'dataProvider'=>$dataProvider,
            'roles'=>$roles
        ]);
    }

    public function actionAddPermission()
    {
        $permission = $this->clear(Yii::$app->request->post('name'));
        if ($permission
            && $this->validate($permission, $this->pattern4Permission)
            && $this->isUnique($permission, 'permission')
        ) {
            $post=Yii::$app->request->post();
            
            $permit = Yii::$app->authManager->createPermission($permission);
            $permit->description = Yii::$app->request->post('description', '');
            Yii::$app->authManager->add($permit);

            if(isset($post['addAnother'])){
                return $this->redirect(Url::toRoute(['add-permission']));
            }
            return $this->redirect(Url::toRoute([
                'permission',
                'name' => $permit->name
            ]));
        }

        return $this->render('updatePermission', ['error' => $this->error]);
    }

    public function actionUpdatePermission($name)
    {
        $permit = Yii::$app->authManager->getPermission($name);
        if ($permit instanceof Permission) {
            $permission = $this->clear(Yii::$app->request->post('name'));
            if ($permission && $this->validate($permission, $this->pattern4Permission)
            ) {
                if($permission!= $name && !$this->isUnique($permission, 'permission'))
                {
                    return $this->render('updatePermission', [
                        'permit' => $permit,
                        'error' => $this->error
                    ]);
                }
                
                $post=Yii::$app->request->post();

                $permit->name = $permission;
                $permit->description = Yii::$app->request->post('description', '');
                Yii::$app->authManager->update($name, $permit);
                
                if(isset($post['addAnother'])){
                    return $this->redirect(Url::toRoute(['add-permission']));
                }
                
                return $this->redirect(Url::toRoute([
                    'update-permission',
                    'name' => $permit->name
                ]));
            }

            return $this->render('updatePermission', [
                'permit' => $permit,
                'error' => $this->error
            ]);
        } else throw new BadRequestHttpException(Yii::t('db_rbac', 'Страница не найдена'));
    }

    public function actionDeletePermission($name)
    {
        $permit = Yii::$app->authManager->getPermission($name);
        if ($permit)
            Yii::$app->authManager->remove($permit);
        return $this->redirect(Url::toRoute(['permission']));
    }

    protected function setAttribute($object, $data)
    {
        $object->name = $data['name'];
        $object->description = $data['description'];
        return $object;
    }

    protected function setPermissions($permissions, $role)
    {
        foreach ($permissions as $permit) {
            $new_permit = Yii::$app->authManager->getPermission($permit);
            Yii::$app->authManager->addChild($role, $new_permit);
        }
    }

    protected function validate($field, $regex)
    {
        $validator = new RegularExpressionValidator(['pattern' => $regex]);
        if ($validator->validate($field, $error))
            return true;
        else {
            $this->error[] = Yii::t('db_rbac', 'Значение "{field}" содержит не допустимые символы', ['field' => $field]);
            return false;
        }
    }

    protected function isUnique($name, $type)
    {
        if ($type == 'role') {
            $role = Yii::$app->authManager->getRole($name);
            if ($role instanceof Role) {
                $this->error[] = Yii::t('db_rbac', 'Роль с таким именем уже существует: ') . $name;
                return false;
            } else return true;
        } elseif ($type == 'permission') {
            $permission = Yii::$app->authManager->getPermission($name);
            if ($permission instanceof Permission) {
                $this->error[] = Yii::t('db_rbac', 'Правило с таким именем уже существует: ') . $name;
                return false;
            } else return true;
        }
    }

    protected function clear($value)
    {
        if (!empty($value)) {
            $value = trim($value, "/ \t\n\r\0\x0B");
        }

        return $value;
    }
}