<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\Group;
use common\models\Settings;
use common\models\DocPatterns;
use common\models\DocPatternCategories;
use common\models\DocPatternGroupCross;

/**
 * Site controller
 */
class SettingsController extends Controller
{
	public $aaa;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
			'access' => [
				'class' => \backend\behaviors\AccessBehavior::className(),
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
	
	public function actionIndex()
	{	
        $model=Settings::itemList();
        
        $result=[];
        foreach($model as $el) $result[$el->groupId][]=$el;

        return $this->render('index', [
            'result' => $result,
            'group'=>Settings::$groupList,
        ]);
	}
	
	public function actionPatterns()
	{	
		if (isset($_GET['a'])) {
			if ($_GET['a'] == 'delCat' && isset($_GET['id']) && ($id = intval($_GET['id'])) > 0) {
				if (DocPatternCategories::find()->where(['parentId' => $id])->count() == 0) {
					if (DocPatterns::find()->where(['categoryId' => $id])->count() == 0) {
						$docPatternCategory = DocPatternCategories::findOne($id);
						$docPatternCategory->delete();
					} else {
						Yii::$app->session->setFlash('error', 'Эта категория содержит паттерны');
						return $this->redirect(Yii::$app->UrlManager->createUrl('/settings/patterns'));
					}
				} else {
					Yii::$app->session->setFlash('error', 'Эта категория содержит подкатегории');
					return $this->redirect(Yii::$app->UrlManager->createUrl('/settings/patterns'));
				}
			}
			
			if ($_GET['a'] == 'delPattern' && isset($_GET['id']) && ($id = intval($_GET['id'])) > 0) {
				$docPatterns = DocPatterns::findOne($id);
				$docPatterns->delete();
			}
		}
        $categoriesTree = [];

		foreach (DocPatternCategories::find()->where(['parentId' => 0])->orderBy('sortOrder')->asArray()->all() as $cat) {
			$categoriesTree[$cat['id']] = $cat;
			$categoriesTree[$cat['id']]['children'] = [];
			$categoriesTree[$cat['id']]['patterns'] = DocPatterns::find()->orderBy('sortOrder')->where([
				'categoryId' => $cat['id']
			])->asArray()->all();
		}

		foreach (DocPatternCategories::find()->where('parentId != 0')->orderBy('sortOrder')->asArray()->all() as $cat) {
			foreach ($categoriesTree as $catId=>$categoryTree) {
				if ($catId == $cat['parentId']) {
					$categoriesTree[$catId]['children'][$cat['id']] = $cat;
					$categoriesTree[$catId]['children'][$cat['id']]['patterns'] = DocPatterns::find()->orderBy('sortOrder')->where([
						'categoryId' => $cat['id']
					])->asArray()->all();
				}
			}
		}

        return $this->render('patterns', [
			'categoriesTree' => $categoriesTree
        ]);
	}
	
	public function actionEditPattern() {
		if (isset($_GET['id']) && ($id = intval($_GET['id']))>0) {
			$model = DocPatterns::findOne($id);
			if ($model->id) {
				if ($model->load(Yii::$app->request->post())) {
					$model->save();
					
					if (isset($_POST['groups']) && is_array($_POST['groups']) && count($_POST['groups'])) {
						DocPatternGroupCross::deleteAll(sprintf("patternId = %s", $model->id));
						foreach ($_POST['groups'] as $groupId) {
							$docPatternGroupCross = new DocPatternGroupCross();
							$docPatternGroupCross->patternId = $model->id;
							$docPatternGroupCross->groupId = $groupId;
							$docPatternGroupCross->save();
						}
					}
					
					return $this->redirect(Yii::$app->UrlManager->createUrl('/settings/patterns'));
				}
				
				$categoriesTree = [];

				foreach (DocPatternCategories::find()->where(['parentId' => 0])->orderBy('sortOrder')->asArray()->all() as $cat) {
					$categoriesTree[$cat['id']] = $cat;
					$categoriesTree[$cat['id']]['children'] = [];
				}
				
				foreach (DocPatternCategories::find()->where('parentId != 0')->orderBy('sortOrder')->asArray()->all() as $cat) {
					foreach ($categoriesTree as $catId=>$categoryTree) {
						if ($catId == $cat['parentId']) {
							$categoriesTree[$catId]['children'][$cat['id']] = $cat;
						}
					}
				}
				
				return $this->render('_form-pattern', [
					'groups' => Group::find()->all(),
					'mode' => 'edit',
					'model' => $model,
					'categoriesTree' => $categoriesTree
				]);
			}
		}
		
		return $this->redirect(Yii::$app->UrlManager->createUrl('/settings/patterns')); 
	}
	
	public function actionAddPattern() {
		$model = new DocPatterns();
		
		if (isset($_GET['categoryId'])) {
			$model->categoryId = $_GET['categoryId'];
		}
		
		if ($model->load(Yii::$app->request->post())) {
			$model->save();
			
			if (isset($_POST['groups']) && is_array($_POST['groups']) && count($_POST['groups'])) {
				foreach ($_POST['groups'] as $groupId) {
					$docPatternGroupCross = new DocPatternGroupCross();
					$docPatternGroupCross->patternId = $model->id;
					$docPatternGroupCross->groupId = $groupId;
					$docPatternGroupCross->save();
				}
			}
			
			return $this->redirect(Yii::$app->UrlManager->createUrl('/settings/patterns'));
		}
		
		$categoriesTree = [];

		foreach (DocPatternCategories::find()->where(['parentId' => 0])->orderBy('sortOrder')->asArray()->all() as $cat) {
			$categoriesTree[$cat['id']] = $cat;
			$categoriesTree[$cat['id']]['children'] = [];
		}
		
		foreach (DocPatternCategories::find()->where('parentId != 0')->orderBy('sortOrder')->asArray()->all() as $cat) {
			foreach ($categoriesTree as $catId=>$categoryTree) {
				if ($catId == $cat['parentId']) {
					$categoriesTree[$catId]['children'][$cat['id']] = $cat;
				}
			}
		}
		
        return $this->render('_form-pattern', [
			'model' => $model,
			'categoriesTree' => $categoriesTree
        ]);
	}
	
	public function actionEditCategory() {       
		if (isset($_GET['categoryId'])) {
			$model = DocPatternCategories::findOne($_GET['categoryId']);

			if ($model->load(Yii::$app->request->post())) {
				$model->save();
				return $this->redirect(Yii::$app->UrlManager->createUrl('/settings/patterns'));
			}
			$categoriesTree = [];

			foreach (DocPatternCategories::find()->where(['parentId' => 0])->orderBy('sortOrder')->asArray()->all() as $cat) {
				$categoriesTree[$cat['id']] = $cat;
				$categoriesTree[$cat['id']]['children'] = [];
			}
			
			foreach (DocPatternCategories::find()->where('parentId != 0')->orderBy('sortOrder')->asArray()->all() as $cat) {
				foreach ($categoriesTree as $catId=>$categoryTree) {
					if ($catId == $cat['parentId']) {
						$categoriesTree[$catId]['children'][$cat['id']] = $cat;
					}
				}
			}
			
			return $this->render('_form-pattern-category', [
				'mode' => 'edit',
				'model' => $model,
				'categoriesTree' => $categoriesTree
			]);
		}
		
		return $this->redirect(Yii::$app->UrlManager->createUrl('/settings/patterns'));
	}
	
	
    public function actionAddCategory() {       
		$model = new DocPatternCategories();
		
		if (isset($_GET['categoryId'])) {
			$model->parentId = $_GET['categoryId'];
		}
		
		if ($model->load(Yii::$app->request->post())) {
			$model->save();
			return $this->redirect(Yii::$app->UrlManager->createUrl('/settings/patterns'));
		}
		$categoriesTree = [];

		foreach (DocPatternCategories::find()->where(['parentId' => 0])->orderBy('sortOrder')->asArray()->all() as $cat) {
			$categoriesTree[$cat['id']] = $cat;
			$categoriesTree[$cat['id']]['children'] = [];
		}
		
		foreach (DocPatternCategories::find()->where('parentId != 0')->orderBy('sortOrder')->asArray()->all() as $cat) {
			foreach ($categoriesTree as $catId=>$categoryTree) {
				if ($catId == $cat['parentId']) {
					$categoriesTree[$catId]['children'][$cat['id']] = $cat;
				}
			}
		}
		
        return $this->render('_form-pattern-category', [
			'model' => $model,
			'categoriesTree' => $categoriesTree
        ]);
	}
    
    public function actionDelete($id=null){
        Settings::delById($id);
        return $this->redirect('/settings/index');
    }
    
    public function actionCreate(){
        $result=$this->actionEdit($id=null);
        return $this->render('_form', $result); 
    }
    
    public function actionUpdate($id=null){
        $result=$this->actionEdit($id);
        return $this->render('_form', $result); 
    }
    
    public function actionEdit($id=null){       
        if($id && is_numeric($id)){
            $model=Settings::oneById($id);
        }
  		else{
            $model=new Settings();
  		}
        
        if(isset($_POST["Settings"])){
            $model->attributes=$_POST["Settings"];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/settings/index');
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
        }
        
        $groupList=Settings::$groupList;
        
        return [
            'model'=>$model,
            'groupList'=>$groupList,
        ];
    }
}