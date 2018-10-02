<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\Json;

use yii\helpers\Url;
use common\models\Billing;
use common\models\Tarif;
use common\models\TarifSettings;
use common\models\TarifList;
use common\models\Company;

use yii\widgets\ActiveForm;
/**
 * Site controller
 */
class TarifController extends Controller
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
    
    public function actionIndex(){
        $dataProvider = new ActiveDataProvider([
            'query' =>Tarif::listAllItemsProvider(),
        ]);
        
        return $this->render('index', [
            'dataProvider'=>$dataProvider,
        ]);
	}
    
    public function actionView($id=0){
        $model=Tarif::oneById($id);
        return $this->render('view', [
            'model'=>$model,
            'dateList'=>Tarif::dateList()
        ]);
    }
    
    public function actionDelete($id=null){
        $model = Tarif::oneById($id);
        if($model->delete()) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
        $this->redirect('/tarif');
    }
    
    public function actionAdd(){
        return $this->edit();
    }
    
    public function actionUpdate($id){
        return $this->edit($id);
    }
    
    private function edit($id=0){
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $model=new Tarif();            
            $model->load(Yii::$app->request->post());
            Yii::$app->response->format = 'json';
            return ActiveForm::validate($model);
        }
                        
        $isNew=false;
        if($id){
            $model=Tarif::oneById($id);
        }
        else{
            $modelTarif=Tarif::find()->orderBy(["dateEnd" => SORT_DESC ])->one();
            
            $model=new Tarif();
            $model->creatorId=Yii::$app->user->id;
            $model->dateStart=$modelTarif->dateEnd?$modelTarif->dateEnd:date('Y-m-d');
            $model->dateEnd=date('Y-m-d',strtotime($model->dateStart.' +1 month'));
            $isNew=true;
        }
        
        if($post=Yii::$app->request->post('Tarif')){
            if($model->id){
                $postNew['dateStart']=$post['dateStart'];
                $postNew['dateEnd']=$post['dateEnd'];
                $postNew['text']=$post['text'];
                unset($post);
                $post=$postNew;
            }
            $model->attributes=$post;
            if($model->save())  Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
            else{
                Yii::$app->session->setFlash('danger', 'Ошибка сохранения данных');
                return $this->refresh();
            }    
            
            if($isNew)  return $this->redirect('/tarif/setting?id='.$model->id);
            return $this->redirect('/tarif');
        }
        return $this->render('_form', [
            'model'=>$model,
            'dateList'=>Tarif::dateList()
        ]);
    }
    
    public function actionCompany($id=null){
        $model=Company::companyById($id);

        $dataProvider = new ActiveDataProvider([
            'query' =>TarifList::itemListByCompanyIdProvider($id),
        ]);
        
        return $this->render('company', [
            'dataProvider'=>$dataProvider,
            'model'=>$model
        ]);
    }
    
    public function actionDeletesetting($id=null){
        $model=TarifSettings::oneById($id);
        $tarifId=$model->tarifId;
        $model->delete();
        $this->redirect('/tarif/setting?id='.$tarifId);
    }
    
    public function actionSettingadd($id=null){
        $model=new TarifSettings();
        $model->tarifId=$id;
        $model->price=150;
        $model->range1=1;
        $model->save();
        $this->redirect('/tarif/setting?id='.$id);
    }
    
    public function actionSetting($id=null){
        if($post=Yii::$app->request->post()){
            $items = TarifSettings::find()->where(['tarifId'=>$id])->all();

            if (TarifSettings::loadMultiple($items, $post) && TarifSettings::validateMultiple($items)){
                foreach ($items as $el) {
                    $el->save();
                }    
                Yii::$app->session->setFlash('success', "Данные успешно сохранены");
            }
            else{
                Yii::$app->session->setFlash('dange', "Ошибка сохранения данных");
            }           
            
            //if($model->save())   Yii::$app->session->setFlash('success', "Данные успешно сохранены");
            //else    
            $this->redirect('/tarif/view/'.$id);
        }
        $model=Tarif::oneById($id);
        
        if(!$model->settings){
            $modelSetting=new TarifSettings();
            $modelSetting->tarifId=$model->id;
            $modelSetting->price=150;
            $modelSetting->range1=1;
            $modelSetting->save();
            $model=Tarif::oneById($id);
        }
        
        return $this->render('setting', [
            'model'=>$model
        ]);
    }
    
}