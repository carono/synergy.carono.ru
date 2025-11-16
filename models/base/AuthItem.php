<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%auth_item}}".
 *
 * @property string $name
 * @property integer $type
 * @property string $description
 * @property string $rule_name
 * @property resource $data
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property \app\models\AuthAssignment[] $authAssignments
 * @property \app\models\AuthItemChild[] $authItemChildren
 * @property \app\models\AuthItemChild[] $authItemChildren0
 * @property \app\models\AuthItem[] $children
 * @property \app\models\AuthItem[] $parents
 * @property \app\models\AuthRule $ruleName
 */
class AuthItem extends ActiveRecord
{
	protected $_relationClasses = ['rule_name' => 'app\models\AuthRule'];


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
		[['description', 'rule_name', 'data'], 'default', 'value' => null],
		      [['name', 'type'], 'required'],
		      [['type'], 'integer'],
		      [['description', 'data'], 'string'],
		      [['name', 'rule_name'], 'string', 'max' => 64],
		      [['name'], 'unique'],
		      [['rule_name'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\AuthRule::class, 'targetAttribute' => ['rule_name' => 'name']],
		      [['name', 'description', 'rule_name'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%auth_item}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\AuthItem|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\AuthItem not found"));
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
		    'type' => Yii::t('models', 'Type'),
		    'description' => Yii::t('models', 'Description'),
		    'rule_name' => Yii::t('models', 'Rule Name'),
		    'data' => Yii::t('models', 'Data'),
		    'created_at' => Yii::t('models', 'Created At'),
		    'updated_at' => Yii::t('models', 'Updated At')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\AuthItemQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\AuthItemQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\AuthAssignmentQuery|\yii\db\ActiveQuery
	 */
	public function getAuthAssignments()
	{
		return $this->hasMany(\app\models\AuthAssignment::class, ['item_name' => 'name']);
	}


	/**
	 * @return \app\models\query\AuthItemChildQuery|\yii\db\ActiveQuery
	 */
	public function getAuthItemChildren()
	{
		return $this->hasMany(\app\models\AuthItemChild::class, ['parent' => 'name']);
	}


	/**
	 * @return \app\models\query\AuthItemChildQuery|\yii\db\ActiveQuery
	 */
	public function getAuthItemChildren0()
	{
		return $this->hasMany(\app\models\AuthItemChild::class, ['child' => 'name']);
	}


	/**
	 * @return \app\models\query\AuthItemQuery|\yii\db\ActiveQuery
	 */
	public function getChildren()
	{
		return $this->hasMany(\app\models\AuthItem::class, ['name' => 'child'])->viaTable('{{%auth_item_child}}', ['parent' => 'name']);
	}


	/**
	 * @return \app\models\query\AuthItemQuery|\yii\db\ActiveQuery
	 */
	public function getParents()
	{
		return $this->hasMany(\app\models\AuthItem::class, ['name' => 'parent'])->viaTable('{{%auth_item_child}}', ['child' => 'name']);
	}


	/**
	 * @return \app\models\query\AuthRuleQuery|\yii\db\ActiveQuery
	 */
	public function getRuleName()
	{
		return $this->hasOne(\app\models\AuthRule::class, ['name' => 'rule_name']);
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
