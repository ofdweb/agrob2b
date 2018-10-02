<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Url;

use common\models\Okpd;
use common\models\OkpdGraphs;

class AjaxController extends Controller
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
						'actions'=>['login','error'],
						'roles' => ['?'],
					],
					[
						'allow' => true,
						'roles' => ['admin'],
					],
				],
				'denyCallback' => function ( $rule , $action ) { 
					Yii::$app->getResponse()->redirect(Yii::$app->UrlManager->createUrl(Url::to('login')));
				} 
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
	
	public function actionOkpdSearch() {
		if (isset($_POST['query'])) {
			$_POST['query'] = addslashes($_POST['query']);

			$_POST['query'] = trim($_POST['query']);
			$words = explode(' ', $_POST['query']);

			if (Okpd::find()->where(sprintf("name LIKE '%%%s%%'", $_POST['query']))->count() > 0) {
				$okpds = Okpd::find()->select('id')->where(sprintf("name LIKE '%%%s%%'", $_POST['query']))->asArray()->all();
			} else {
				$where = "(name LIKE '%".join("%' OR name LIKE '%", $words)."%')";
				$okpds = Okpd::find()->select('id')->where($where)->asArray()->all();
			}
			
			$chIds = []; $pIds = [];
			
			foreach ($okpds as $okpd) {
				$chIds[] = $okpd['id'];
			}
			
			if (count($chIds) > 0) {
				$graphs = OkpdGraphs::find()->where(sprintf('childId in (%s)', join(', ', $chIds)))->asArray()->all();
				foreach ($graphs as $graph) {
					$pIds[] = $graph['parentId'];
				}
			}
			
			$ids = array_unique(array_merge($chIds, $pIds));

			$selected = (is_array(Yii::$app->request->post('selected'))) ? Yii::$app->request->post('selected') : [];

			$list = []; $grandchildren = [];
			if (count($ids) > 0) {
				
				$childs = OkpdGraphs::find()->select('parentId')->where(sprintf('parentId in (%s)', join(',', $ids)))->asArray()->all();
				$_childs = [];
				
				foreach ($childs as $child) {
					$_childs[] = $child['parentId'];
				}
				
				$grandchildren = array_diff($ids, $_childs);
				//pre($grandchildren);
				
				$tree = Okpd::getSearchResultTree($ids);
				
				//pre($tree);

				$inPage = 2;
				$pagination = [
					'pages' => ceil(count($tree) / $inPage),
					'inpage' => $inPage,
					'page' => (Yii::$app->request->post('page')) ? Yii::$app->request->post('page') : 1,
					'count' => count($tree)
				];
				if ($pagination['page'] > $pagination['pages']) $pagination['page'] = $pagination['pages'];
				$pagination['from'] = ($pagination['page']-1) * $inPage;
				$pagination['to'] = $pagination['page'] * $inPage;

				//pre($pagination);

				$_tree = []; $i = 0;				
				foreach ($tree as $id=>$item) {
					if ($i >= $pagination['from'] && $i < $pagination['to']) {
						$_tree[$id] = $item;
					}
					$i++;
				}
				
				$tree = $_tree;
				unset($_tree);
				
				foreach ($tree as $e) {
					$list[] = [
						'level' => 0,
						'id' => $e['id'],
						'name' => $e['name'],
						'code' => $e['code'],
					];
					
					if ($e['children']) {
						Okpd::_getTreeForSelect2($e['children'], $list, 1);
					}
				}
				
				foreach ($list as &$item) {
					$item['sname'] = $item['name'];
					foreach ($words as $word) {
						$item['sname'] = preg_replace("/(".preg_quote($word).")/iu", "<span class=fined>\\1</span>", $item['sname']);
						//$item['name'] = str_ireplace($word, "<span style='background-color: #46b8da; color: white;'>".$word.'</span>', $item['name']);
					}
				}
			}
			
			return $this->renderPartial('_okpd_search_result', [
				'okpds' => $list,
				'selected' => $selected,
				'grandchildren' => $grandchildren,
				'pagination' => $pagination,
				'multiSelect' => (isset($_POST['multiSelect'])) ? Yii::$app->request->post('multiSelect') : 1
			]);
			
		}
	}

	
	public function actionGetOkpdTree() {
		if (isset($_POST['parent'])) {
			if ($parentId = Yii::$app->request->post('parent')) {
				$okpd = Okpd::findOne($parentId);
				
				$parents = $okpd->getParentBranch();
				//pre($parents);
				
				$selected = (is_array(Yii::$app->request->post('selected'))) ? Yii::$app->request->post('selected') : [];
				
				return $this->renderPartial('_okpd_tree', [
					'model' => $okpd,
					'parents' => $parents,
					'selected' => $selected,
					'childs' => Okpd::find()->where(['parentId' => $okpd->id])->all()
				]);
			} else {
				return $this->renderPartial('_okpd_tree', [
					'okpds' => Okpd::find()->where(['level' => 1])->all()
				]);
			}
		}
	}
	
	
}
