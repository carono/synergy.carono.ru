<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%user}}".
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $deleted_at
 *
 * @property \app\models\Comment[] $comments
 */
class User extends ActiveRecord
{
	protected $_relationClasses = [];


	public function behaviors()
	{
		return [    'softDeleteBehavior' => [
		        'class' => 'yii2tech\ar\softdelete\SoftDeleteBehavior',
		        'softDeleteAttributeValues' => [
		            'deleted_at' => new \yii\db\Expression('NOW()'),
		        ],
		        'restoreAttributeValues'=> ['deleted_at' => null],
		        'replaceRegularDelete' => true
		    ]];
	}


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
		[['username', 'password_hash', 'deleted_at'], 'default', 'value' => null],
		      [['deleted_at'], 'safe'],
		      [['username', 'password_hash'], 'string', 'max' => 255],
		      [['username', 'password_hash'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%user}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\User|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\User not found"));
		}else{
		    return $model;
		}
	}


	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
		    'id' => Yii::t('models', 'ID'),
		    'username' => Yii::t('models', 'Username'),
		    'password_hash' => Yii::t('models', 'Password Hash'),
		    'deleted_at' => Yii::t('models', 'Deleted At')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\UserQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\UserQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\CommentQuery|\yii\db\ActiveQuery
	 */
	public function getComments()
	{
		return $this->hasMany(\app\models\Comment::class, ['user_id' => 'id']);
	}


	/**
	 * @param string $attribute
	 * @return string|null
	 */
	public function getRelationClass($attribute)
	{
		return ArrayHelper::getValue($this->_relationClasses, $attribute);
	}
}
