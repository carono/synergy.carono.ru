<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%semester}}".
 *
 * @property integer $id
 * @property string $name
 * @property integer $course_id
 * @property string $controller
 * @property integer $pos
 * @property string $deleted_at
 * @property string $created_at
 *
 * @property \app\models\Case[] $cases
 * @property \app\models\Course $course
 */
class Semester extends ActiveRecord
{
	protected $_relationClasses = ['course_id' => 'app\models\Course'];


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
		[['name', 'course_id', 'controller', 'deleted_at'], 'default', 'value' => null],
		      [['pos'], 'default', 'value' => 10],
		      [['course_id', 'pos'], 'integer'],
		      [['deleted_at'], 'safe'],
		      [['name', 'controller'], 'string', 'max' => 255],
		      [['course_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\Course::class, 'targetAttribute' => ['course_id' => 'id']],
		      [['name', 'controller'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%semester}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\Semester|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\Semester not found"));
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
		    'course_id' => Yii::t('models', 'Course ID'),
		    'controller' => Yii::t('models', 'Controller'),
		    'pos' => Yii::t('models', 'Pos'),
		    'created_at' => Yii::t('models', 'Created At'),
		    'deleted_at' => Yii::t('models', 'Deleted At')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\SemesterQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\SemesterQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\CaseQuery|\yii\db\ActiveQuery
	 */
	public function getCases()
	{
		return $this->hasMany(\app\models\Case::class, ['semester_id' => 'id']);
	}


	/**
	 * @return \app\models\query\CourseQuery|\yii\db\ActiveQuery
	 */
	public function getCourse()
	{
		return $this->hasOne(\app\models\Course::class, ['id' => 'course_id']);
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
