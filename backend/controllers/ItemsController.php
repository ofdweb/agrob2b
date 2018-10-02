<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

use common\models\Items;
use common\models\Currency;
use common\models\CurrencyHistory;
use common\models\Themes;


use common\models\ElevatorFiles;
use common\models\FtpFiles;
use frontend\models\Elevators;
use frontend\models\ElevatorsItem;
use common\models\UploadedFileCustom;

/**
 * Site controller
 */
class ItemsController extends Controller
{
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
	
	public function actionElevators() {
		$queryModel = new Elevators();
		$query = Elevators::find()->orderBy(["id" => SORT_DESC ]);
		if($name = Yii::$app->request->get('Elevators')) {
			$queryModel->name = current($name);
			$query->where(['like', "name" , current($name)]);
		}

		$dataProvider = new ActiveDataProvider([
			'query' =>$query,
		]);

		return $this->render('elevators/list', [
			'dataProvider' => $dataProvider,
			'queryModel' => $queryModel,
		]);
	}
	
	public function actionAddFileElevatorItem($id,$categoryId) {
		if($id && is_numeric($id)) {
			$ftpFile = new FtpFiles();
			$ftpFile->scenario = FtpFiles::CATEGORY_FILES;

			if($post = Yii::$app->request->post()) {
				if (($ftpFile->file = UploadedFileCustom::getInstance($ftpFile, 'file')) && $ftpFile->validate()) {
					if ($ftpFile->file->tempName) {
						$ftpFile->file->server = $ftpFile->ftpServersDefault[FtpFiles::CATEGORY_FILES];
						$ftpFile->sha1 = sha1_file($ftpFile->file->tempName);

						$path = sprintf('/%s/%s', FtpFiles::CATEGORY_FILES, $ftpFile->sha1);

						if ($ftpFile->file->saveAs($path, $ftpFile->file->name, false)) {
							// если стоит модуль юзаем его, иначе берем инфу из сопроводительной инфы
							if (function_exists('finfo_open')) {
								$finfo = finfo_open(FILEINFO_MIME);
								if ($finfo) {
									$ftpFile->mime = finfo_file($finfo, $ftpFile->file->tempName);
								}
							} else {
								$ftpFile->mime = $ftpFile->file->type;
							}

							$ftpFile->title = 'TTN file for  elevator/category';
							$ftpFile->name = $ftpFile->file->name;
							$ftpFile->size = filesize($ftpFile->file->tempName);
							$ftpFile->userId = Yii::$app->user->getId();
							$ftpFile->companyId = $companyId;
							$ftpFile->ftpServer = $ftpFile->file->server;
							$ftpFile->category = FtpFiles::CATEGORY_FILES;

							if ($ftpFile->save()) {
							}
						} else {
							$ftpFile->addError('file', join(', ', $ftpFile->file->errors));
						}
					} else if (!$ftpFile->file->size) Yii::$app->session->setFlash('danger', Yii::t('controller', 'Не удалось добавить файл {file}. Превышен допустимый размер файла.', ['file' => $ftpFile->name]));
					ElevatorFiles::updateAll(['forDelete' => 1], ['elevatorId' => $id, 'categoryId' => $categoryId]);
					$fileId = $ftpFile->id;
					$elevatorFile = new ElevatorFiles();
					$elevatorFile->elevatorId = $id;
					$elevatorFile->forDelete = 0;
					$elevatorFile->ftpFileId = $fileId;
					$elevatorFile->categoryId = $categoryId;
					$elevatorFile->save();
				}
				return $this->redirect('/items/elevators');
			}
			return $this->render('elevators/create',['ftpFile' => $ftpFile]);
		} return false;
	}
	
	public function actionRemoveFilesElevatorItem($id,$categoryId){
		ElevatorFiles::updateAll(['forDelete' => 1], ['elevatorId' => $id, 'categoryId' => $categoryId]);
		return $this->redirect('/items/elevators');
	}
	
	public function actionListCategoryElevator($id) {
		if($id && is_numeric($id)) { 
			
			$model = new ElevatorsItem();
			
			$query = ElevatorsItem::find()->where(['elevatorId' => $id, 'actual' => 1])->with(['category', 'elevatorItemFile.files'])->orderBy(['id' => SORT_DESC]);
			
			$dataProvider = new ActiveDataProvider([
				'query' => $query,
			]);
			return $this->render('elevators/listCategory', ['model' => $model, 'dataProvider' => $dataProvider, 'id' => $id]);
		} return false;
	}
	
	public function actionList()
	{	
        $items=Items::getTree();

        return $this->render('manual/list', [
            'tree' => $items,
        ]);
	}

	public function actionViewmanual($id='')
	{
		if($id && is_numeric($id)){
			if($model=Items::findOne(['id'=>$id])){         
                $items=ArrayHelper::map(Items::find()->All(), 'id', 'name');
    			return $this->render('manual/view', [
    				'model' => $model,
                    'items'=>$items,

    			]);
            }
            else $this->redirect('/items/list');
		}
	}

	public function actionDeletemanual($id='')
	{
	   if($id && is_numeric($id)){
            if(Items::deleteall(['id'=>$id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
            $this->redirect('/items/list');
	   }
	}
		
	public function actionUpdatemanual($id='')
	{
		echo $this->actionEditManual($id);
	}
	public function actionCreatemanual($id='')
	{
		echo $this->actionEditManual($id);
	}
	
	public function actionEditManual($id='')
	{
        if($id && is_numeric($id)){
	       $model=Items::findOne(['id'=>$id]);
        }
  		else{
            $model=new Items();
  		}
        
        $items=ArrayHelper::map(Items::allParents(), 'id', 'name');
        $items[0]='Корень';
        
        if(isset($_POST["Items"])){
            $model->attributes=$_POST["Items"];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/items/viewmanual/'.$model->id);
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
            //$model=UserFormEdit::update($id,$attr);
            //if(!$model->getErrors())    return $this->redirect('/users/'.$model->id);
        }

		return $this->render('manual/_form', [
			'model' => $model,
            'items'=>$items,
		]);
	}
    
    
    
    /**
     * currency
     */
    public function actionCurrency()
	{	
        $model=new Currency();
        $query=Currency::find()->orderBy(["id" => SORT_DESC ]);
        
         if(isset($_GET['Currency'])){
            $params=$_GET['Currency'];
            foreach($params as $key=>$el){
                    if($el){
                        $query->andWhere(['like', $key, $el]);
                        $model->$key=$el;
                    }
            }
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);
        
        return $this->render('currency/list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
	}
    
    public function actionCurrencyhistory(){
         $dataProvider = new ActiveDataProvider([
            'query' =>CurrencyHistory::find(),
        ]);
        return $this->render('currency/history', [
            'dataProvider' => $dataProvider,
        ]);
    }
    
    public function actionCurrencyconvert(){
        $model=new Currency();
        $curList=ArrayHelper::map(Currency::itemList(), 'iso3', 'name');
        
        $value=0;
        $attr=[];
        if($attr=$_POST){
            $value=Currency::calculate($attr['value'],$attr['from'],$attr['to']);
        }
        
        return $this->render('currency/calculate', [
            'model' => $model,
            'curList'=>$curList,
            'value'=>$value,
            'attr'=>$attr
        ]);
    }

	public function actionViewcurrency($id='')
	{
		if($id && is_numeric($id)){
			if($model=Currency::findOne(['id'=>$id])){         
                
    			return $this->render('currency/view', [
    				'model' => $model,

    			]);
            }
            else $this->redirect('/items/list');
		}
	}
    
    public function actionDeletecurrency($id='')
	{
	   if($id && is_numeric($id)){
            if(Currency::deleteall(['id'=>$id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
            $this->redirect('/items/currency');
	   }
	}
		
	public function actionUpdatecurrency($id='')
	{
		echo $this->actionEditCurrency($id);
	}
	public function actionCreatecurrency($id='')
	{
		echo $this->actionEditCurrency($id);
	}
	
	public function actionEditCurrency($id='')
	{
        if($id && is_numeric($id)){
	       $model=Currency::findOne(['id'=>$id]);
        }
  		else{
            $model=new Currency();
  		}
        
        if(isset($_POST["Currency"])){
            $model->attributes=$_POST["Currency"];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/items/viewcurrency/'.$model->id);
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
            //$model=UserFormEdit::update($id,$attr);
            //if(!$model->getErrors())    return $this->redirect('/users/'.$model->id);
        }

		return $this->render('currency/_form', [
			'model' => $model,
            'items'=>$items,
		]);
	}
    
    /**
     * weight
     */
    public function actionWeight()
	{	
        $model=new Items();
        $query=Items::find()->where(['parentId'=>93])->orderBy(["id" => SORT_DESC ]);
        
         if(isset($_GET['Items'])){
            $params=$_GET['Items'];
            foreach($params as $key=>$el){
                    if($el){
                        $query->andWhere(['like', $key, $el]);
                        $model->$key=$el;
                    }
            }
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);
        
        return $this->render('weight/list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
	}
    
    public function actionUpdateweight($id='')
	{
		echo $this->actionEditWeight($id);
	}
	public function actionCreateweight($id='')
	{
		echo $this->actionEditWeight($id);
	}
	
	public function actionEditWeight($id='')
	{
        if($id && is_numeric($id)){
	       $model=Items::findOne(['id'=>$id]);
        }
  		else{
            $model=new Items();
            $model->parentId=93;
            $model->nameI18n='{"en":""}';
            $model->shortNameI18n='{"en":""}';
  		}
        
        if(isset($_POST["Items"])){
            $model->attributes=$_POST["Items"];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/items/weight');
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
            //$model=UserFormEdit::update($id,$attr);
            //if(!$model->getErrors())    return $this->redirect('/users/'.$model->id);
        }

		return $this->render('weight/_form', [
			'model' => $model,
		]);
	}
    
    public function actionDeleteweight($id='')
	{
	   if($id && is_numeric($id)){
            if(Items::deleteall(['id'=>$id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
            $this->redirect('/items/weight');
	   }
	}
    
    /**
     * themes
     */
    public function actionThemes()
	{	
        $model=new Themes();
        $query=Themes::find()->orderBy(["id" => SORT_DESC ]);
        
         if(isset($_GET['Themes'])){
            $params=$_GET['Themes'];
            foreach($params as $key=>$el){
                    if($el){
                        $query->andWhere(['like', $key, $el]);
                        $model->$key=$el;
                    }
            }
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);
        
        return $this->render('themes/list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
	}
    
    public function actionViewtheme($id='')
	{
		if($id && is_numeric($id)){
			if($model=Themes::findOne(['id'=>$id])){         
                
    			return $this->render('themes/view', [
    				'model' => $model,

    			]);
            }
            else $this->redirect('/items/themes');
		}
	}
    
    public function actionDeletetheme($id='')
	{
	   if($id && is_numeric($id)){
            if(Themes::deleteall(['id'=>$id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
            $this->redirect('/items/themes');
	   }
	}

    public function actionUpdatetheme($id='')
	{
		echo $this->actionEditTheme($id);
	}
	public function actionCreatetheme($id='')
	{
		echo $this->actionEditTheme($id);
	}
	
	public function actionEditTheme($id='')
	{
        if($id && is_numeric($id)){
	       $model=Themes::findOne(['id'=>$id]);
        }
  		else{
            $model=new Themes();
  		}
        
        if(isset($_POST["Themes"])){
            $model->attributes=$_POST["Themes"];
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены');    
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/items/viewtheme/'.$model->id);
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
            //$model=UserFormEdit::update($id,$attr);
            //if(!$model->getErrors())    return $this->redirect('/users/'.$model->id);
        }

		return $this->render('themes/_form', [
			'model' => $model,
		]);
	}
}