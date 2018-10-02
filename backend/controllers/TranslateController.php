<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;

use yii\web\UploadedFile;


use common\models\Translate;

/**
 * Site controller
 */
class TranslateController extends Controller
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

    public function actionIndex($cat = null)
    {

        $catList = require(Yii::getAlias('@common') . '/config/langFileMap.php');
        //$catList=Translate::$catList;

        $model = new Translate();

        //$model->category = $cat ? $cat : '';
        //$query = $model::itemListByCatIdProvider($model->category);

        if (isset($_GET['Translate']['category'])) {
            $model->category = $_GET['Translate']['category'];
        }

        $query = Translate::find();
        if ($attr = $_GET) {
            //pre($attr);
            if ($attr['rus']) $query->andFilterWhere(['like', 'rus', $attr['rus']]);
            if ($attr['eng']) $query->andFilterWhere(['like', 'eng', $attr['eng']]);
            if ($attr['rusAlt']) $query->andFilterWhere(['like', 'rusAlt', $attr['rusAlt']]);
            if ($attr['comment']) $query->andFilterWhere(['like', 'comment', $attr['comment']]);
            if ($model->category) $query->andFilterWhere(['like', 'category', $model->category]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('index', [
            'catList' => $catList,
            'model' => $model,
            'dataProvider' => $dataProvider,
            'catName' => $catList[$model->category]
        ]);
    }

    public function actionEmpty()
    {
        $catList = require(Yii::getAlias('@common') . '/config/langFileMap.php');
        //$catList=Translate::$catList;

        $model = new Translate();

        if (isset($_GET['Translate']['category'])) {
            $model->category = $_GET['Translate']['category'];
        }

        $query = $model::itemListEmptyProvider();
        if ($attr = $_GET) {
            if ($attr['rus']) $query->andFilterWhere(['like', 'rus', $attr['rus']]);
            if ($attr['eng']) $query->andFilterWhere(['like', 'eng', $attr['eng']]);
            if ($attr['rusAlt']) $query->andFilterWhere(['like', 'rusAlt', $attr['rusAlt']]);
            if ($model->category) $query->andFilterWhere(['like', 'category', $model->category]);
            if ($attr['comment']) $query->andFilterWhere(['like', 'comment', $attr['comment']]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render('empty', [
            'catList' => $catList,
            'model' => $model,
            'dataProvider' => $dataProvider,
            'catName' => 'Не переведенные'
        ]);
    }

    public function actionCreate()
    {
        if ($attr = Yii::$app->request->post('Translate')) {
            $model = new Translate();
            $model->attributes = $attr;

            if ($model->validate() && $model->save()) Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
            else Yii::$app->session->setFlash('error', 'Ошибка добавления данных');
        }
        return $this->redirect('/translate?cat=' . $attr['category']);
    }

    public function actionDelete($id = null)
    {
        $model = Translate::oneById($id);
        $cat = $model->category;
        $model->delete();
        return $this->redirect('/translate?cat=' . $cat);
    }

    public function actionUpdate()
    {
        Yii::$app->response->format = 'json';
        $status = false;

        if ($attr = Yii::$app->request->post()) {
            if ($model = Translate::oneById($attr['id'])) {
                $model->{$attr['name']} = $attr['value'];
                if ($model->save()) $status = true;
            }
        }
        return ['status' => $status];
    }

    public function actionTranslate()
    {
        Yii::$app->response->format = 'json';
        $text = null;
        if ($attr = Yii::$app->request->post()) {
            $text = Translate::translate($attr['text']);
        }
        return ['text' => $text];
    }

    public function actionGenerate($cat = null)
    {
        if ($attr = Yii::$app->request->post('Translate')) {
            $catList = require(Yii::getAlias('@common') . '/config/langFileMap.php');
            //$catList=Translate::$catList;

            if ($cat == 'all') {
                foreach ($catList as $key => $el) {
                    $this->generateRus($catList[$key], $key);
                    $this->generateEng($catList[$key], $key);
                    $this->generateRusId($catList[$key], $key);
                }
            } else {
                $this->generateRus($catList[$attr['category']], $attr['category']);
                $this->generateEng($catList[$attr['category']], $attr['category']);
                $this->generateRusId($catList[$attr['category']], $attr['category']);
            }

            Yii::$app->session->setFlash('success', 'Файл успешно перезаписан');
            return $this->redirect('/translate?id=' . $attr['category']);
        }
    }

    private function generateRus($fileName = null, $catId = null)
    {
        if ($fileName && $catId) {
            $result = "<?php return [\r\n";
            foreach (Translate::itemListByCatId($catId) as $el) {
                $el->rus = str_replace("'", '"', $el->rus);
                //$el->rus = str_replace('"','&quot;',$el->rus);

                $result .= sprintf("'%s' => '%s', \r\n\r\n", $el->rusAlt, $el->rus);
            }
            $result .= "];";

            $file = fopen(Yii::getAlias('@frontend') . "/messages/ru/" . $fileName, "w");
            fwrite($file, $result);
            fclose($file);
        }
    }

    private function generateEng($fileName = null, $catId = null)
    {
        if ($fileName && $catId) {
            $result = "<?php return [\r\n";
            foreach (Translate::itemListByCatId($catId) as $el) {
                $el->eng = str_replace("'", '"', $el->eng);
                //$el->eng = str_replace('"','&quot;',$el->eng);

                $result .= sprintf("'%s' => '%s',\r\n\r\n", $el->rusAlt, $el->eng);
            }
            $result .= "];";

            $file = fopen(Yii::getAlias('@frontend') . "/messages/en/" . $fileName, "w");
            fwrite($file, $result);
            fclose($file);
        }
    }

    private function generateRusId($fileName = null, $catId = null)
    {
        if ($fileName && $catId) {
            $result = "<?php return [\r\n";
            foreach (Translate::itemListByCatId($catId) as $el) {
                //$el->rusAlt=str_replace(['"',"'"],['\"',"\'"],$el->rusAlt);
                $el->rus = str_replace(['"', "'"], ['\"', "\'"], $el->rus);
                $result .= "'" . $el->rusAlt . "'=>'" . $el->id . '-' . $el->rus . "',\r\n";
            }
            $result .= "];";

            $file = fopen(Yii::getAlias('@frontend') . "/messages/tu/" . $fileName, "w");
            fwrite($file, $result);
            fclose($file);
        }
    }

    public function actionUpload()
    {
        if (Yii::$app->request->post()) {
            $file = UploadedFile::getInstanceByName('file');
            if ($file) {
                require_once Yii::getAlias('@backend') . '/modules/CRM/controllers/PHPExcel/PHPExcel/IOFactory.php';
                require_once Yii::getAlias('@backend') . '/modules/CRM/controllers/PHPExcel/PHPExcel.php';

                $file->saveAs($file->name);

                $objPHPExcel = \PHPExcel_IOFactory::load($file->name);
                unlink($file);

                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
                    $highestRow = $worksheet->getHighestRow(); // например, 10

                    for ($row = 2; $row <= $highestRow; ++$row) {
                        $id = $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                        $attr = [
                            'rusAlt' => $worksheet->getCellByColumnAndRow(2, $row)->getValue(),
                            'rus' => $worksheet->getCellByColumnAndRow(3, $row)->getValue(),
                            'eng' => $worksheet->getCellByColumnAndRow(4, $row)->getValue(),
                            'comment' => $worksheet->getCellByColumnAndRow(5, $row)->getValue(),
                        ];
                        if ($attr && !empty($attr['rusAlt'])) {
                            if ($model = Translate::oneById($id)) {
                                $model->attributes = $attr;
                                $model->save();
                            }
                        }
                    }
                }
                Yii::$app->session->setFlash('success', 'Файл успешно импортирован');
            } else Yii::$app->session->setFlash('error', 'Ошибка загрузки файла');
        }
        return $this->redirect('/translate');
    }

    public function actionExcel($all = 0)
    {
        if ($attr = Yii::$app->request->post('Translate')) {
            if ($all) {
                $title = 'Переводы все';
                $model = Translate::find()->All();
            } else {
                $catList = require(Yii::getAlias('@common') . '/config/langFileMap.php');
                $cat = $catList[$attr['category']];
                $title = 'Переводы ' . ($cat ? $cat : 'не переведенных');
                $model = $cat ? Translate::itemListByCatId($attr['category']) : Translate::itemListEmptyProvider()->All();
            }


            $row = 1;
            $rows[$row][] = 'id';
            $rows[$row][] = 'Категория';
            $rows[$row][] = 'Русский системный';
            $rows[$row][] = 'Русский';
            $rows[$row][] = 'Английский';
            $rows[$row][] = 'Комментарий';

            foreach ($model as $key => $el) {
                $row++;
                $rows[$row][] = $el->id;
                $rows[$row][] = $el->category;
                $rows[$row][] = $el->rusAlt;
                $rows[$row][] = $el->rus;
                $rows[$row][] = $el->eng;
                $rows[$row][] = $el->comment;
            }

            require_once Yii::getAlias('@backend') . '/modules/CRM/controllers/PHPExcel/PHPExcel/IOFactory.php';
            require_once Yii::getAlias('@backend') . '/modules/CRM/controllers/PHPExcel/PHPExcel.php';

            $xls = new \PHPExcel();
            $xls->setActiveSheetIndex(0);
            $sheet = $xls->getActiveSheet();

            $sheet->setTitle($title);
            $filename = ' ' . $title . ' ' . date('Y.m.d') . '.xls';
            $sheet->fromArray($rows);
            $columns_count = \PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
            for ($column = 0; $column <= $columns_count; $column++) {
                $adjustedColumn = \PHPExcel_Cell::stringFromColumnIndex($column);
                $sheet->getColumnDimension($adjustedColumn)->setAutoSize(TRUE);
            }

            $file = Yii::getAlias('@backend') . '/' . $filename;

            $objWriter = \PHPExcel_IOFactory::createWriter($xls, 'Excel5');
            $objWriter->save($file);

            header('Content-Description: File Transfer');
            header('Content-Type: application/xls');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            unlink($file);
            exit;
        }
    }

}