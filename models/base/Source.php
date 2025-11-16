<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\base;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the base-model class for table "{{%source}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $view
 * @property integer $pos
 * @property integer $case_id
 * @property string $class
 * @property string $method
 * @property string $file
 *
 * @property \app\models\Case $case
 * @property \app\models\Comment[] $comments
 */
class Source extends ActiveRecord
{
	protected $_relationClasses = ['case_id' => 'app\models\Case'];


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
		[['name', 'description', 'view', 'case_id', 'class', 'method', 'file'], 'default', 'value' => null],
		      [['pos'], 'default', 'value' => 10],
		      [['pos', 'case_id'], 'integer'],
		      [['name', 'description', 'view', 'class', 'method', 'file'], 'string', 'max' => 255],
		      [['case_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\Case::class, 'targetAttribute' => ['case_id' => 'id']],
		      [['name', 'description', 'view', 'class', 'method', 'file'], 'trim']
		];
	}


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%source}}';
	}


	/**
	 * @inheritdoc
	 * @return \app\models\Source|\yii\db\ActiveRecord
	 */
	public static function findOne($condition, $raise = false)
	{
		$model = parent::findOne($condition);
		if (!$model && $raise){
		    throw new \yii\web\HttpException(404, Yii::t('errors', "Model app\\models\\Source not found"));
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
		    'description' => Yii::t('models', 'Description'),
		    'view' => Yii::t('models', 'View'),
		    'pos' => Yii::t('models', 'Pos'),
		    'case_id' => Yii::t('models', 'Case ID'),
		    'class' => Yii::t('models', 'Class'),
		    'method' => Yii::t('models', 'Method'),
		    'file' => Yii::t('models', 'File')
		];
	}


	/**
	 * @inheritdoc
	 * @return \app\models\query\SourceQuery the active query used by this AR class.
	 */
	public static function find()
	{
		return new \app\models\query\SourceQuery(get_called_class());
	}


	/**
	 * @return \app\models\query\CaseQuery|\yii\db\ActiveQuery
	 */
	public function getCase()
	{
		return $this->hasOne(\app\models\Case::class, ['id' => 'case_id']);
	}


	/**
	 * @return \app\models\query\CommentQuery|\yii\db\ActiveQuery
	 */
	public function getComments()
	{
		return $this->hasMany(\app\models\Comment::class, ['source_id' => 'id']);
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
