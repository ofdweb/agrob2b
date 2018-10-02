<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);


return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
	'modules' => [
		'articles' => [
			'class' => 'backend\modules\Articles\Module',
		],
		'pages' => [
			'class' => 'backend\modules\Pages\Module',
		],		
		'permit' => [
			'class' => 'backend\modules\RBAC\Yii2DbRbac',
		],
        'crm' => [
			'class' => 'backend\modules\CRM\Module',
		],
		'webmail' => [
			'class' => 'backend\modules\webmail\Module',
		],
		'accespanel' => [
			'class' => 'backend\modules\AccesPanel\Module',
		],
        'dictionary' => [
			'class' => 'backend\modules\Dictionary\Module',
		],
	],
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
		'urlManager' => [
			'enablePrettyUrl' => true,
			'showScriptName' => false,
			'rules' => [
				'' => 'site/index',
				'<action:(login|logout|about)>' => 'site/<action>',
				'<_c:[\w\-]+>/<id:\d+>' => '<_c>/view',
				'<_c:[\w\-]+>' => '<_c>/index',
				'<_c:[\w\-]+>/<_a:[\w\-]+>/<id:\d+>' => '<_c>/<_a>',
                [
                    'class' => 'yii\rest\UrlRule',  
                    'controller' => ['accespanel/rest/authorize' => 'accespanel/rest/authorize'],
                    'extraPatterns' => [
                        'GET' => 'auth',
                        'GET oauth' => 'oauth',
                        'GET, POST code' => 'code',
                        'POST token' => 'token',
                        'POST refresh' => 'refresh'
                    ],
                    'except' => ['delete', 'view', 'update', 'view'],
                ],
			],
		],
		'db' => require(__DIR__ . '/../../common/config/db.php'),
		/*
		'view' => [
            'renderers' => [
                'tpl' => [
                    'class' => 'yii\smarty\ViewRenderer',
                    //'cachePath' => '@runtime/Smarty/cache',
                ],
            ],
        ],*/
    ],
    'params' => $params,
];
