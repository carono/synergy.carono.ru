<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models;

use Yii;

/**
 * This is the model class for table "user".
 */
class User extends base\User implements \yii\web\IdentityInterface
{
    public $password;

    #[\Override] public static function findIdentity($id)
    {
        return User::find()->notDeleted()->andWhere(['id' => $id])->one();
    }

    #[\Override] public static function findIdentityByAccessToken($token, $type = null)
    {
        // TODO: Implement findIdentityByAccessToken() method.
    }

    #[\Override] public function getId()
    {
        return $this->id;
    }

    #[\Override] public function getAuthKey()
    {
        // TODO: Implement getAuthKey() method.
    }

    #[\Override] public function validateAuthKey($authKey)
    {
        // TODO: Implement validateAuthKey() method.
    }

    public static function findByUsername($username)
    {
        return User::find()->notDeleted()->andWhere(['username' => $username])->one();
    }

    public function beforeValidate()
    {
        if ($this->password) {
            $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
        }
        return parent::beforeValidate();
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }
}
