<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace dektrium\user;

use yii\base\BootstrapInterface;
use yii\web\GroupUrlRule;
use yii\console\Application as ConsoleApplication;

/**
 * Bootstrap class registers module and user application component. It also creates some url rules which will be applied
 * when UrlManager.enablePrettyUrl is enabled.
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        /** @var $module Module */
        if ($app->hasModule('user') && ($module = $app->getModule('user')) instanceof Module){
            foreach ($module->modelMap as $name => $definition) {
                $class = "dektrium\\user\\models\\" . $name;
                \Yii::$container->set($class, $definition);
                if (is_array($definition)) {
                    $module->modelMap = [$name => $class];
                }
                \Yii::$container->set($name . 'Query', function () use ($module, $name) {
                    return forward_static_call([$module->modelMap[$name], 'find']);
                });
            }
            \Yii::$container->setSingleton(Finder::className(), [
                'userQuery'    => \Yii::$container->get('UserQuery'),
                'profileQuery' => \Yii::$container->get('ProfileQuery'),
                'tokenQuery'   => \Yii::$container->get('TokenQuery'),
                'accountQuery' => \Yii::$container->get('AccountQuery'),
            ]);
            \Yii::$container->set('yii\web\User', [
                'enableAutoLogin' => true,
                'loginUrl'        => ['/user/security/login'],
                'identityClass'   => $module->modelMap['User'],
            ]);

            if ($app instanceof ConsoleApplication) {
                $module->controllerNamespace = 'dektrium\user\commands';
            } else {
                $configUrlRule = [
                    'prefix' => $module->urlPrefix,
                    'rules'  => $module->urlRules
                ];

                if ($module->urlPrefix != 'user') {
                    $configUrlRule['routePrefix'] = 'user';
                }

                $app->get('urlManager')->rules[] = new GroupUrlRule($configUrlRule);

                if (!$app->has('authClientCollection')) {
                    $app->set('authClientCollection', [
                        'class' => 'yii\authclient\Collection',
                    ]);
                }
            }

            $app->get('i18n')->translations['user*'] = [
                'class'    => 'yii\i18n\PhpMessageSource',
                'basePath' => __DIR__ . '/messages',
            ];
        }
        
    }
}