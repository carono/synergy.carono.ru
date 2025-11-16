<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%task}}".
 *
 * @property integer $id
 * @property string $name
 * @property integer $semester_id
 * @property string $description
 * @property string $comment
 *
 * @property \app\models\Semester $semester
 * @property \app\models\Source[] $sources
 */
class Task extends ActiveRecord
{
	protected $_relationClasses = ['semester_id' => 'app\models\Semester'];


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
		[['name', 'semester_id', 'description', 'comment'], 'default', 'value' => null],
		      [['semester_id'], 'integer'],
		      [['description', 'comment'], 'string'],
		      [['name'], 'string', 'max' => 255],
		      [['semester_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\Semester::class, 'targetAttribute' => ['semester_id' => 'id']],
		      [['name', 'description', 'comment'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%task}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\Task|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\Task not found"));
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
		    'semester_id' => Yii::t('models', 'Semester ID'),
		    'description' => Yii::t('models', 'Description'),
		    'comment' => Yii::t('models', 'Comment')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\TaskQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\TaskQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\SemesterQuery|\yii\db\ActiveQuery
	 */
	public function getSemester()
	{
		return $this->hasOne(\app\models\Semester::class, ['id' => 'semester_id']);
	}


	/**
	 * @return \app\models\query\SourceQuery|\yii\db\ActiveQuery
	 */
	public function getSources()
	{
		return $this->hasMany(\app\models\Source::class, ['task_id' => 'id']);
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
