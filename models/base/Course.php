<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%course}}".
 *
 * @property integer $id
 * @property string $name
 * @property integer $pos
 * @property string $module
 * @property string $deleted_at
 * @property string $created_at
 *
 * @property \app\models\Semester[] $semesters
 */
class Course extends ActiveRecord
{
	protected $_relationClasses = [];


	public function behaviors()
	{
		return [    'timestamp' => [
		        'class' => 'yii\behaviors\TimestampBehavior',
		        'value' => new \yii\db\Expression('NOW()'),
		        'createdAtAttribute' => 'created_at',
		        'updatedAtAttribute' => null
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
		[['name', 'module', 'deleted_at'], 'default', 'value' => null],
		      [['pos'], 'default', 'value' => 10],
		      [['pos'], 'integer'],
		      [['deleted_at'], 'safe'],
		      [['name', 'module'], 'string', 'max' => 255],
		      [['name', 'module'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%course}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\Course|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\Course not found"));
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
		    'name' => Yii::t('models', 'Name'),
		    'pos' => Yii::t('models', 'Pos'),
		    'module' => Yii::t('models', 'Module'),
		    'created_at' => Yii::t('models', 'Created At'),
		    'deleted_at' => Yii::t('models', 'Deleted At')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\CourseQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\CourseQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\SemesterQuery|\yii\db\ActiveQuery
	 */
	public function getSemesters()
	{
		return $this->hasMany(\app\models\Semester::class, ['course_id' => 'id']);
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
