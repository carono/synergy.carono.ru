<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%auth_item_child}}".
 *
 * @property string $parent
 * @property string $child
 *
 * @property \app\models\AuthItem $child0
 * @property \app\models\AuthItem $parent0
 */
class AuthItemChild extends ActiveRecord
{
	protected $_relationClasses = ['child' => 'app\models\AuthItem', 'parent' => 'app\models\AuthItem'];


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
		[['parent', 'child'], 'required'],
		      [['parent', 'child'], 'string', 'max' => 64],
		      [['parent', 'child'], 'unique', 'targetAttribute' => ['parent', 'child']],
		      [['parent'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\AuthItem::class, 'targetAttribute' => ['parent' => 'name']],
		      [['child'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\AuthItem::class, 'targetAttribute' => ['child' => 'name']],
		      [['parent', 'child'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%auth_item_child}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\AuthItemChild|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\AuthItemChild not found"));
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
		    'parent' => Yii::t('models', 'Parent'),
		    'child' => Yii::t('models', 'Child')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\AuthItemChildQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\AuthItemChildQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\AuthItemQuery|\yii\db\ActiveQuery
	 */
	public function getChild0()
	{
		return $this->hasOne(\app\models\AuthItem::class, ['name' => 'child']);
	}


	/**
	 * @return \app\models\query\AuthItemQuery|\yii\db\ActiveQuery
	 */
	public function getParent0()
	{
		return $this->hasOne(\app\models\AuthItem::class, ['name' => 'parent']);
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
