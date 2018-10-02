<?php
namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;

use yii\helpers\ArrayHelper;
use common\components\THelper;
use backend\models\IpBaning;
use backend\models\IpInfo;
use yii\helpers\Url;

/**
 * Site controller
 */
class IpbaningController extends Controller
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
	
	public function actionList()
	{	
        $query=IpBaning::itemListProveder();
        if($attr=$_GET){
            if($attr['uid']) $query->andFilterWhere(['like', 'uid', $attr['uid']]);
            if($attr['ip']) $query->andFilterWhere(['like', 'ip', $attr['ip']]);
            if($attr['baningTime']) $query->andFilterWhere(['like', 'baningTime', $attr['baningTime']]);
            if($attr['dateAdd']) $query->andFilterWhere(['like', 'dateAdd', $attr['dateAdd']]);
        }
        
		
        $dataProvider = new ActiveDataProvider([
            'query' =>$query,
        ]);
        
        $model=new IpBaning();
        $userList=[0=>'Все']+THelper::cmap($model->itemList(), 'uid', ['user.lastName','user.name'],' ');

        return $this->render('list', [
            'dataProvider' => $dataProvider,
            'model'=>$model,
            'userList'=>$userList
        ]);
	}
    
    public function actionAdd($ip=null){
        if($ip){
            IpBaning::add($ip);
            $this->redirect($_SERVER['HTTP_REFERER']);
        }
        else if($attr=$_POST["IpBaning"]){
            IpBaning::add($attr['ip']);
            $this->redirect($_SERVER['HTTP_REFERER']);
        }
    }
    
    public function actionDel($ip=null){
        if($ip){
            IpBaning::del($ip);
            $this->redirect($_SERVER['HTTP_REFERER']);
        }
    }
    
    public function actionIpinfo($ip=null){
        if($ip || $ip=$_POST["IpInfo"]['ip']){
            if(!$model=IpInfo::oneByIp($ip)){
                $data=$this->getURL('http://whatismyipaddress.com/ip/'.$ip);

                preg_match_all('#<table[^>]*>(.*?)</table>#is', $data, $tables);
                preg_match_all('#<td[^>]*>(.*?)</td>#is', $tables[0][0], $td1);
                preg_match_all('#<td[^>]*>(.*?)</td>#is', $tables[0][1], $td2);

                if($td1[0] && $td2[0]){
                    $model=new IpInfo();
                    
                    $model->ip=strip_tags($td1[0][0]);
                    $model->decimal=(int)strip_tags($td1[0][1]);
                    $model->hostName=strip_tags($td1[0][2]);
                    $model->isp=strip_tags($td1[0][3]);
                    $model->organization=strip_tags($td1[0][4]);
                    $model->services=strip_tags($td1[0][5]);
                    $model->type=strip_tags($td1[0][6]);
                    $model->assignment=strip_tags($td1[0][7]);
                    
                    $model->country=strip_tags($td2[0][0]);
                    $model->region=strip_tags($td2[0][1]);
                    $model->city=strip_tags($td2[0][2]);
                    $model->latitude=strip_tags($td2[0][3]);
                    $model->longitude=strip_tags($td2[0][4]);
                    $model->postalCode=(int)strip_tags($td2[0][5]);
                    
                    $model->save();
                }
                else $model=new IpInfo();
            }
        }
        else{
            $model=new IpInfo();
        }
        return $this->render('info',['model'=>$model]);
    }
    
    private function getURL($url, $data = array()) {
            $http_options =[
    			'cookiestore' =>'/tmp/rest-client-cookie',
                'AGENT' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)',
    			'CONNECTTIMEOUT' => 20
        	];
            
            $ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $url);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $http_options["CONNECTTIMEOUT"]);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $http_options["CONNECTTIMEOUT"]); 
            curl_setopt($ch, CURLOPT_USERAGENT, $http_options["AGENT"]);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type:application/html')); 
            curl_setopt($ch,CURLOPT_VERBOSE,1);
            curl_setopt($ch,CURLOPT_POST,0);
            curl_setopt ($ch, CURLOPT_REFERER,'http://www.google.com');
        	
            $res=curl_exec($ch);
            curl_close($ch);
        	
        	return $res;
   	}
}