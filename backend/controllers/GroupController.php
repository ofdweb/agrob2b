<?php

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;

use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

use common\components\ImageAction\Image;
use common\models\Okpd;
use common\models\OkpdGraphs;
use common\models\Company;
use common\components\THelper;
use common\models\DocPatternCategories;
use common\models\Files;
use common\models\DocPatterns;
use common\models\DocPatternGroupCross;
use common\models\Group;

use backend\models\GroupCompanyRelations;
use backend\models\GroupOkpdRelations;
use backend\models\Group as GroupWidget;

use frontend\models\Widgets;
use frontend\models\UserWidgets;
use frontend\models\DefaultWidgetsByGroup;

/**
 * GroupController implements the CRUD actions for Group model.
 */
class GroupController extends Controller
{
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
     * Lists all Group models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Group::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Group model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model=$this->findModel($id);
        $pathToImage=Files::pathToFile($model->imageId,'thumb_group');
        
        return $this->render('view', [
            'model' => $model,
            'pathToImage'=>$pathToImage
        ]);
    }

    /**
     * Creates a new Group model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Group();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Group model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $pathToImage=Files::pathToFile($model->imageId,'thumb_group');

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if($file=UploadedFile::getInstance($model, 'file')){
                $modelFile= new Files();
                $modelFile->file=$file;
                
                if($modelFile->upload('group')){
                    Yii::$app->session->setFlash('success', sprintf(Yii::t('controller', 'Файл %s добавлен'), $modelFile->title));
                    $model->imageId=$modelFile->id;
                            
                    if($imageThumb=Image::load($modelFile->pathToFile)){
                        $imageThumb->resize('thumb_group', 'precise');
                        //$image->saveToFTP();
                        $imageThumb->saveToLocal();     
                    }    
                }
                else {
                    $model->addError('file', join(', ', $modelFile->file->errors));
                    Yii::$app->session->setFlash('danger', sprintf(Yii::t('controller', 'Не удалось добавить файл %s'), $modelFile->title));
                }
            }
            
            $model->save();
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'pathToImage'=>$pathToImage
            ]);
        }
    }

    /**
     * Deletes an existing Group model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }
    
    public function actionCompany($id=null){
        $query=GroupCompanyRelations::itemListProvider($id);
        $model = $this->findModel($id);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        
        $companyList=THelper::cmapCompany(Company::itemList(), 'id', ['nameShort','id','active'],' ; ');

        return $this->render('company', [
            'dataProvider' => $dataProvider,
            'model' => $model,
            'companyList'=>$companyList
        ]);
    }
    
    public function actionCompanyAdd($groupId=null){ 
        if(($post=Yii::$app->request->post('company_list')) && $groupId){
            $fields=[
                'offer'=>Yii::$app->request->post('offer'),
                'demand'=>Yii::$app->request->post('demand'),
            ];
            foreach($post as $el){
                GroupCompanyRelations::addItem($groupId,$el,['fields'=>$fields]);
            }
            Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
        }
        return $this->redirect(['/group/company/', 'id' => $groupId]);
    }
    
    public function actionCompanyDel($groupId=null,$companyId=null){
        if($groupId && $companyId){
            if(GroupCompanyRelations::delItem($groupId,$companyId))  Yii::$app->session->setFlash('success', 'Компания удалена из списка');
            else    Yii::$app->session->setFlash('danger', 'Не удалось удалить компанию');
        }
        return $this->redirect(['/group/company/', 'id' => $groupId]);
    }
    
    public function actionCompanyDelAll($groupId=null){
        if(($selected=Yii::$app->request->post('selection')) && $groupId){ 
            $post=Yii::$app->request->post();

            if(isset($post['save'])){
                foreach($selected as $el){
                    if($model=GroupCompanyRelations::oneById($groupId,$el)){
                        $model->offer=isset($post['offer'][$el])?$post['offer'][$el]:0;
                        $model->demand=isset($post['demand'][$el])?$post['demand'][$el]:0;
                        $model->save();
                    }
                }
                Yii::$app->session->setFlash('success', 'Данные успешно сохранены'); 
            }
            else if(isset($post['remove'])){
                foreach($selected as $el){
                    GroupCompanyRelations::delItem($groupId,$el);
                }
                Yii::$app->session->setFlash('success', 'Компании удалены из списка');    
            }
        }
        else{
            Yii::$app->session->setFlash('danger', 'Выберите один или несколько пунктов');
        }
        return $this->redirect(['/group/company/', 'id' => $groupId]);
    }
    
    public function actionPattern($id=null){
        $model = Group::findOne($id);
		
		if (isset($_POST['patterns']) && is_array($_POST['patterns']) && count($_POST['patterns'])) {
			DocPatternGroupCross::deleteAll(sprintf("groupId = %s", $model->id));
			foreach ($_POST['patterns'] as $patternId) {
				$docPatternGroupCross = new DocPatternGroupCross();
				$docPatternGroupCross->patternId = $patternId;
				$docPatternGroupCross->groupId = $model->id;
				$docPatternGroupCross->save();
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

        return $this->render('pattern', [
			'model' => $model,
			'categoriesTree' => $categoriesTree
        ]);
    }
    
    public function actionOkpd($id=null){
        $query=GroupOkpdRelations::itemListProvider($id);
        $model = $this->findModel($id);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        
        return $this->render('okpd', [
            'dataProvider' => $dataProvider,
            'model' => $model,
            'okpdList'=>Okpd::getTreeList()
        ]);
    }
    
    public function actionOkpdAdd($groupId=null){
        if(($post=Yii::$app->request->post('okpd_list')) && $groupId){
            foreach($post as $el){
                GroupOkpdRelations::addItem($groupId,$el);
            }
            Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
        }
        return $this->redirect(['/group/okpd/', 'id' => $groupId]);
    }
    
    public function actionOkpdDel($groupId=null,$okpdId=null){ 
        if($groupId && $okpdId){
            if(GroupOkpdRelations::delItem($groupId,$okpdId))  Yii::$app->session->setFlash('success', 'ОКПД удален из списка');
            else    Yii::$app->session->setFlash('danger', 'Не удалось удалить ОКПД');
        }
        return $this->redirect(['/group/okpd/', 'id' => $groupId]);
    }
    
    public function actionOkpdDelAll($groupId=null){
        if(($selected=Yii::$app->request->post('selection')) && $groupId){ 
            foreach($selected as $el){
                GroupOkpdRelations::delItem($groupId,$el);
            }
            Yii::$app->session->setFlash('success', 'ОКПД удалены из списка');
        }
        else if(($post=Yii::$app->request->post('GroupOkpdRelations')) && isset($_POST['addImage'])){ 
                $modelOkpd=GroupOkpdRelations::oneById($groupId,$post['okpdId']); 
                if($modelOkpd && ($file=UploadedFile::getInstance($modelOkpd, 'file'))){
                    $modelFile=new Files();
                    $modelFile->file=$file;
                    
                    if($modelFile->upload('group_okpd')){
                        Yii::$app->session->setFlash('success', sprintf(Yii::t('controller', 'Файл %s добавлен'), $modelFile->title));
                        $modelOkpd->imageId=$modelFile->id;
                        $modelOkpd->save();
                                
                        if($imageThumb=Image::load($modelFile->pathToFile)){
                            $imageThumb->resize('thumb_okpd', 'precise');
                            //$image->saveToFTP();
                            $imageThumb->saveToLocal();     
                        }    
                    }
                    else {
                        $model->addError('file', join(', ', $modelFile->file->errors));
                        Yii::$app->session->setFlash('danger', sprintf(Yii::t('controller', 'Не удалось добавить файл %s'), $modelFile->title));
                    }
                }
        }
        else if($post=Yii::$app->request->post('GroupOkpdRelations')){
            if($groupId && $post['okpdId']){
                if(GroupOkpdRelations::delItem($groupId,$post['okpdId']))  Yii::$app->session->setFlash('success', 'ОКПД удален из списка');
                else    Yii::$app->session->setFlash('danger', 'Не удалось удалить ОКПД');
            }
        }
        else{
            Yii::$app->session->setFlash('danger', 'Не удалось удалить ОКПД. Выберите один или несколько пунктов');
        }
        return $this->redirect(['/group/okpd/', 'id' => $groupId]);
    }

    /**
     * Finds the Group model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Group the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Group::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
	
	public function actionWidgets() {
		$dataProvider = new ActiveDataProvider([
            'query' => Widgets::find(),
        ]);
		return $this->render('/widgets/index',['dataProvider' => $dataProvider]);
	}
	
	public function actionCreatewidget() {
		if($attr=Yii::$app->request->post()) {

            $attr=isset($attr['Widgets'])?$attr['Widgets']:$attr['Widgets'];

			$model = new Widgets();
			$model->attributes=$attr;
			$model->save();

			return $this->redirect('/group/widgets');
        }
		else $model= new Widgets();
        $groups =  ArrayHelper::map(GroupWidget::find()->with('defaultwidgetsgroups')->All(), 'id', 'title');
		$defaultWidgets = new DefaultWidgetsByGroup();
		return $this->render('/widgets/create', [
			'model' => $model,
			'defaultWidgets' => $defaultWidgets,
			'groups' => $groups
		]);
	}
	
	public function actionUpdatewidget($id='') {
		if($attr=Yii::$app->request->post()) {
			$attrs=isset($attr['Widgets'])?$attr['Widgets']:$attr['Widgets'];
			$groups=isset($attr['DefaultWidgetsByGroup'])?$attr['DefaultWidgetsByGroup']:$attr['DefaultWidgetsByGroup'];
			
			DefaultWidgetsByGroup::deleteAll('idWidget = :idWidget', [':idWidget' => $id]);
			Widgets::updateAll($attrs, ['id' => $id]);
			
			foreach($groups['idGroup'] as $key => $value) {
				$_new = new DefaultWidgetsByGroup();
				$_new->idGroup = $value;
				$_new->idWidget = $id;
				$_new->save();
			}
			
		} else {
			if($id && is_numeric($id)){
				$model = Widgets::find()->where(['id'=>$id])->one();
			} else {
				$model = new Widgets();
			}
		}
		
		$groups =  ArrayHelper::map(GroupWidget::find()->All(), 'id', 'title');
		$_widgets = ArrayHelper::map(Widgets::find()->All(), 'name', 'action');
		$defaultWidgets = new DefaultWidgetsByGroup();
		$defaultWidgets->idGroup = ArrayHelper::map(DefaultWidgetsByGroup::find()->where(['idWidget' => $id])->all(),'id','idGroup');

		return $this->render('/widgets/update', [
			'model' => $model,
			'defaultWidgets' => $defaultWidgets,
			'groups' => $groups,
			'_widgets' => $_widgets,
		]);
	}
	public function actionDeletewidget($id) {
		if ($id) {
			Widgets::deleteAll('id = :id', [':id' => $id]);
			UserWidgets::deleteAll('idWidget = :idWidget', [':idWidget' => $id]);
			DefaultWidgetsByGroup::deleteAll('idWidget = :idWidget', [':idWidget' => $id]);
			return $this->redirect('/group/widgets');
		}
	}
}
