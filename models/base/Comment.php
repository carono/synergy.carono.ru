<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%comment}}".
 *
 * @property integer $id
 * @property integer $source_id
 * @property integer $user_id
 * @property string $message
 * @property string $deleted_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property \app\models\Source $source
 * @property \app\models\User $user
 */
class Comment extends ActiveRecord
{
	protected $_relationClasses = ['source_id' => 'app\models\Source', 'user_id' => 'app\models\User'];


	public function behaviors()
	{
		return [    'timestamp' => [
		        'class' => 'yii\behaviors\TimestampBehavior',
		        'value' => new \yii\db\Expression('NOW()'),
		        'createdAtAttribute' => 'created_at',
		        'updatedAtAttribute' => 'updated_at'
		    ],
		    'softDeleteBehavior' => [
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
		[['source_id', 'user_id', 'message', 'deleted_at'], 'default', 'value' => null],
		      [['source_id', 'user_id'], 'integer'],
		      [['message'], 'string'],
		      [['deleted_at'], 'safe'],
		      [['source_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\Source::class, 'targetAttribute' => ['source_id' => 'id']],
		      [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\User::class, 'targetAttribute' => ['user_id' => 'id']],
		      [['message'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%comment}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\Comment|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\Comment not found"));
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
		    'source_id' => Yii::t('models', 'Source ID'),
		    'user_id' => Yii::t('models', 'User ID'),
		    'message' => Yii::t('models', 'Message'),
		    'created_at' => Yii::t('models', 'Created At'),
		    'updated_at' => Yii::t('models', 'Updated At'),
		    'deleted_at' => Yii::t('models', 'Deleted At')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\CommentQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\CommentQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\SourceQuery|\yii\db\ActiveQuery
	 */
	public function getSource()
	{
		return $this->hasOne(\app\models\Source::class, ['id' => 'source_id']);
	}


	/**
	 * @return \app\models\query\UserQuery|\yii\db\ActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(\app\models\User::class, ['id' => 'user_id']);
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
