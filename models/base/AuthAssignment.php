<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%auth_assignment}}".
 *
 * @property string $item_name
 * @property string $user_id
 * @property integer $created_at
 *
 * @property \app\models\AuthItem $itemName
 */
class AuthAssignment extends ActiveRecord
{
	protected $_relationClasses = ['item_name' => 'app\models\AuthItem'];


	public function behaviors()
	{
		return [    'timestamp' => [
		        'class' => 'yii\behaviors\TimestampBehavior',

		        'createdAtAttribute' => 'created_at',
		        'updatedAtAttribute' => null
		    ]];
	}


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
		[['item_name', 'user_id'], 'required'],
		      [['item_name', 'user_id'], 'string', 'max' => 64],
		      [['item_name', 'user_id'], 'unique', 'targetAttribute' => ['item_name', 'user_id']],
		      [['item_name'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\AuthItem::class, 'targetAttribute' => ['item_name' => 'name']],
		      [['item_name', 'user_id'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%auth_assignment}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\AuthAssignment|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\AuthAssignment not found"));
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
		    'item_name' => Yii::t('models', 'Item Name'),
		    'user_id' => Yii::t('models', 'User ID'),
		    'created_at' => Yii::t('models', 'Created At')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\AuthAssignmentQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\AuthAssignmentQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\AuthItemQuery|\yii\db\ActiveQuery
	 */
	public function getItemName()
	{
		return $this->hasOne(\app\models\AuthItem::class, ['name' => 'item_name']);
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
