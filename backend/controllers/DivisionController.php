<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

use common\models\Division;
use common\models\Okpd;
use common\models\DivisionOkpdCross;
/**
 * Site controller
 */
class DivisionController extends Controller
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
	
	public function actionList()
	{	
        $tree=Division::getTree();

        return $this->render('list', [
            'tree' => $tree,
        ]);
	}

	public function actionView($id='')
	{
		if($id && is_numeric($id)){
			if($model=Division::getDivision($id)){
                $divisionAll=ArrayHelper::map(Division::find()->All(), 'id', 'name');
				$okpdIds = ArrayHelper::getColumn(DivisionOkpdCross::find()->where(sprintf("divisionId = %s", $model->id))->asArray()->all(), 'okpdId');
				if (count($okpdIds) > 0) {
					$okpds = Okpd::find()->where(sprintf("id in (%s)", join(',', $okpdIds)))->all();
				} else {
					$okpds = [];
				}

    			return $this->render('view', [
    				'model' => $model,
                    'divisionAll'=>$divisionAll,
					'okpds' => $okpds
    			]);
            }
            else $this->redirect('/division/list');
		}
	}

	public function actionDelete($id='')
	{
	   if($id && is_numeric($id)){
            if(Division::deleteall(['id'=>$id])) Yii::$app->session->setFlash('success', 'Данные успешно удалены');
			
			$cross = DivisionOkpdCross::find()->where(sprintf("divisionId = %s", $id))->all();
			foreach ($cross as $cros) $cros->delete();
			
            $this->redirect('/division/list');
	   }
	}
	
	public function actionUpdate($id='')
	{
	   
		echo $this->actionEdit($id);
	}
	public function actionCreate($id=null)
	{
		echo $this->actionEdit($id);
	}
	
	public function actionEdit($id='')
	{
        if($id && is_numeric($id)){
	       $model=Division::getDivision($id);
        }
  		else{
            $model=new Division();
  		}
        
        $divisionAll=ArrayHelper::map(Division::find()->asArray()->All(), 'id', 'name');


        if(isset($_POST["Division"])){
            $attr=$_POST["Division"];
            $is_new=true;
            
			if ($model->id) {
                $is_new=false;
                 
				$cross = DivisionOkpdCross::find()->where(sprintf("divisionId = %s", $model->id))->all();
				foreach ($cross as $cros) {
					$cros->delete();
				}
				
				if (isset($_POST['Division']['okpds'])) {
					foreach ($_POST['Division']['okpds'] as $okpd) {
						$newCross = new DivisionOkpdCross();
						$newCross->divisionId = $model->id;
						$newCross->okpdId = $okpd;
						
						$newCross->save();
					}
				}
			}
            
            $model->attributes=$attr;
            //if(!$model->id) $model->status=0;
            if($model->validate()){
                if($model->save()){
                    Yii::$app->session->setFlash('success', 'Данные успешно сохранены'); 
                    $listChild=[];
                    if($attr['childCreate']=='on' && $is_new){
                        foreach($attr['okpds'] as $okpd){
                            
                            foreach(Okpd::getChildrenIds($okpd) as $el){
                                $modelChild=new Division();
                                $modelChild->name=$el['name'];
                                $modelChild->parentId=isset($listChild[$el['parentId']])?$listChild[$el['parentId']]:$model->id;
                                $modelChild->save();
                                $listChild[$el['id']]=$modelChild->id;
                               // var_dump(Okpd::getChildrenIds($okpd));die;
                            }    
                        }
                        
                    }   
                }
                else Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect('/division/'.$model->id);
            }
            else{
                Yii::$app->session->setFlash('error', 'Заполните все поля');
                return $this->refresh();
            }
        }

		$selectedOkpds = [];
		if ($model->id) {
			$selectedOkpds = ArrayHelper::getColumn(DivisionOkpdCross::find()->where(sprintf("divisionId = %s", $model->id))->asArray()->all(), 'okpdId');
		}
		
		return $this->render('_form', [
			'model' => $model,
            'divisions' => Division::getTreeList(),
            'divisionAll'=> $divisionAll,
			'okpds' => Okpd::getTreeList(),
			'selectedOkpds' => $selectedOkpds
		]);
	}
    
    

}