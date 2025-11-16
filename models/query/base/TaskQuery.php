<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\query\base;

use carono\yii2helpers\QueryHelper;
use yii\data\ActiveDataProvider;
use yii\data\Sort;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for \app\models\Task
 * @see \app\models\Task
 * @method \yii\db\BatchQueryResult|\app\models\Task[] each($batchSize = 100, $db = null)
 * @method \yii\db\BatchQueryResult|\app\models\Task[] batch($batchSize = 100, $db = null)
 */
class TaskQuery extends ActiveQuery
{
	/**
	 * @return $this
	 */
	public function notDeleted()
	{
		return $this->andWhere(['{{%task}}.[[deleted_at]]' => null]);
	}


	/**
	 * @return $this
	 */
	public function available()
	{
		return $this->notDeleted();
	}


	/**
	 * @var array|\yii\db\ActiveRecord $model
	 * @return $this
	 */
	public function filter($model = null)
	{
		if ($model instanceof \app\interfaces\Search){
		    $model->updateQuery($this);
		} elseif ($model instanceof \yii\db\ActiveRecord){
		    QueryHelper::regular($model, $this);
		}
		return $this;
	}


	/**
	 * @var mixed $filter
	 * @var array $options Options for ActiveDataProvider
	 * @return ActiveDataProvider
	 */
	public function search($filter = null, $options = [])
	{
		$class = \yii\helpers\ArrayHelper::remove($options, 'class', ActiveDataProvider::class);
		$sort = new Sort();

		$query = clone $this;
		if (method_exists($query, 'filter')) {
		    $query->filter($filter);
		}

		if ($query->asArray) {
		    $class = \yii\data\ArrayDataProvider::class;
		    $options['allModels'] = $query->all();
		} else {
		    $options['query'] = $query;
		}

		$options['sort'] = $sort;
		$options['class'] = $class;

		return \Yii::createObject($options);
	}


	/**
	 * @inheritdoc
	 * @return \app\models\Task[]
	 */
	public function all($db = null)
	{
		return parent::all($db);
	}


	/**
	 * @inheritdoc
	 * @return \app\models\Task
	 */
	public function one($db = null)
	{
		return parent::one($db);
	}
}
