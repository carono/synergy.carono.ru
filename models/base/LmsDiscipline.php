<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%lms_discipline}}".
 *
 * @property integer $id
 * @property string $lms_id
 * @property integer $semester
 * @property string $title
 * @property string $slug
 * @property string $created_at
 * @property string $updated_at
 *
 * @property \app\models\LmsVideo[] $lmsVideos
 */
class LmsDiscipline extends ActiveRecord
{
	protected $_relationClasses = [];


	public function behaviors()
	{
		return [    'timestamp' => [
		        'class' => 'yii\behaviors\TimestampBehavior',
		        'value' => new \yii\db\Expression('NOW()'),
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
		[['lms_id', 'semester', 'title', 'slug'], 'required'],
		      [['semester'], 'integer'],
		      [['lms_id'], 'string', 'max' => 64],
		      [['title', 'slug'], 'string', 'max' => 255],
		      [['lms_id'], 'unique'],
		      [['lms_id', 'title', 'slug'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%lms_discipline}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\LmsDiscipline|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\LmsDiscipline not found"));
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
		    'lms_id' => Yii::t('models', 'Lms ID'),
		    'semester' => Yii::t('models', 'Semester'),
		    'title' => Yii::t('models', 'Title'),
		    'slug' => Yii::t('models', 'Slug'),
		    'created_at' => Yii::t('models', 'Created At'),
		    'updated_at' => Yii::t('models', 'Updated At')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\LmsDisciplineQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\LmsDisciplineQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\LmsVideoQuery|\yii\db\ActiveQuery
	 */
	public function getLmsVideos()
	{
		return $this->hasMany(\app\models\LmsVideo::class, ['discipline_id' => 'id']);
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
