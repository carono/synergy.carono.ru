<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%lms_video}}".
 *
 * @property integer $id
 * @property integer $discipline_id
 * @property string $lms_resource_id
 * @property string $lesson_type
 * @property string $code
 * @property string $title
 * @property string $status
 * @property string $locked_reason
 * @property integer $required_minutes
 * @property integer $watched_minutes
 * @property string $video_url
 * @property string $file_path
 * @property integer $file_size
 * @property string $transcript_raw
 * @property string $transcript_clean
 * @property string $transcript_summary
 * @property string $transcribed_at
 * @property string $downloaded_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property \app\models\LmsDiscipline $discipline
 */
class LmsVideo extends ActiveRecord
{
	protected $_relationClasses = ['discipline_id' => 'app\models\LmsDiscipline'];


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
		[['discipline_id', 'lesson_type', 'code', 'locked_reason', 'required_minutes', 'watched_minutes', 'video_url', 'file_path', 'file_size', 'transcript_raw', 'transcript_clean', 'transcript_summary', 'transcribed_at', 'downloaded_at'], 'default', 'value' => null],
		      [['status'], 'default', 'value' => 'downloaded'],
		      [['discipline_id', 'required_minutes', 'watched_minutes', 'file_size'], 'integer'],
		      [['lms_resource_id', 'title'], 'required'],
		      [['video_url', 'transcript_raw', 'transcript_clean', 'transcript_summary'], 'string'],
		      [['downloaded_at', 'transcribed_at'], 'safe'],
		      [['lms_resource_id', 'code', 'lesson_type'], 'string', 'max' => 64],
		      [['title', 'locked_reason'], 'string', 'max' => 255],
		      [['status'], 'string', 'max' => 32],
		      [['file_path'], 'string', 'max' => 500],
		      [['discipline_id', 'lms_resource_id'], 'unique', 'targetAttribute' => ['discipline_id', 'lms_resource_id']],
		      [['discipline_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\LmsDiscipline::class, 'targetAttribute' => ['discipline_id' => 'id']],
		      [['lms_resource_id', 'code', 'lesson_type', 'title', 'status', 'locked_reason', 'video_url', 'file_path'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%lms_video}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\LmsVideo|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\LmsVideo not found"));
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
		    'discipline_id' => Yii::t('models', 'Discipline ID'),
		    'lms_resource_id' => Yii::t('models', 'Lms Resource ID'),
		    'lesson_type' => Yii::t('models', 'Lesson Type'),
		    'code' => Yii::t('models', 'Code'),
		    'title' => Yii::t('models', 'Title'),
		    'status' => Yii::t('models', 'Status'),
		    'locked_reason' => Yii::t('models', 'Locked Reason'),
		    'required_minutes' => Yii::t('models', 'Required Minutes'),
		    'watched_minutes' => Yii::t('models', 'Watched Minutes'),
		    'video_url' => Yii::t('models', 'Video Url'),
		    'file_path' => Yii::t('models', 'File Path'),
		    'file_size' => Yii::t('models', 'File Size'),
		    'transcript_raw' => Yii::t('models', 'Transcript Raw'),
		    'transcript_clean' => Yii::t('models', 'Transcript Clean'),
		    'transcript_summary' => Yii::t('models', 'Transcript Summary'),
		    'transcribed_at' => Yii::t('models', 'Transcribed At'),
		    'downloaded_at' => Yii::t('models', 'Downloaded At'),
		    'created_at' => Yii::t('models', 'Created At'),
		    'updated_at' => Yii::t('models', 'Updated At')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\LmsVideoQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\LmsVideoQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\LmsDisciplineQuery|\yii\db\ActiveQuery
	 */
	public function getDiscipline()
	{
		return $this->hasOne(\app\models\LmsDiscipline::class, ['id' => 'discipline_id']);
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
