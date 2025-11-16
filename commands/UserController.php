<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\User;
use carono\yii2\exceptions\ValidationException;
use carono\yii2rbac\RoleManager;
use yii\console\Controller;

class UserController extends Controller
{
    public function actionAddAdmin($login, $password)
    {
        if (!$user = User::findByUsername($login)) {
            $user = new User();
            $user->username = $login;
        }
        $user->password = $password;
        if (!$user->save()) {
            throw new ValidationException($user);
        }
        RoleManager::revokeAll($user);
        RoleManager::assign('admin', $user);
    }

    public function actionAdd($login, $password)
    {
        if (!$user = User::findByUsername($login)) {
            $user = new User();
            $user->username = $login;
        }
        $user->password = $password;
        if (!$user->save()) {
            throw new ValidationException($user);
        }
        RoleManager::revokeAll($user);
    }
}
