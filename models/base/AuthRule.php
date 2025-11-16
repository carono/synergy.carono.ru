<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%auth_rule}}".
 *
 * @property string $name
 * @property resource $data
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property \app\models\AuthItem[] $authItems
 */
class AuthRule extends ActiveRecord
{
	protected $_relationClasses = [];


	public function behaviors()
	{
		return [    'timestamp' => [
		        'class' => 'yii\behaviors\TimestampBehavior',

		        'createdAtAttribute' => 'created_at',
		        'updatedAtAttribute' => 'updated_at'
		    ]];
	}


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
		[['data'], 'default', 'value' => null],
		      [['name'], 'required'],
		      [['data'], 'string'],
		      [['name'], 'string', 'max' => 64],
		      [['name'], 'unique'],
		      [['name'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%auth_rule}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\AuthRule|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\AuthRule not found"));
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
		    'name' => Yii::t('models', 'Name'),
		    'data' => Yii::t('models', 'Data'),
		    'created_at' => Yii::t('models', 'Created At'),
		    'updated_at' => Yii::t('models', 'Updated At')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\AuthRuleQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\AuthRuleQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\AuthItemQuery|\yii\db\ActiveQuery
	 */
	public function getAuthItems()
	{
		return $this->hasMany(\app\models\AuthItem::class, ['rule_name' => 'name']);
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
