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
 * This is the ActiveQuery class for \app\models\AuthRule
 * @see \app\models\AuthRule
 * @method \yii\db\BatchQueryResult|\app\models\AuthRule[] each($batchSize = 100, $db = null)
 * @method \yii\db\BatchQueryResult|\app\models\AuthRule[] batch($batchSize = 100, $db = null)
 */
class AuthRuleQuery extends ActiveQuery
{
	/**
	 * @return $this
	 */
	public function available()
	{
		return $this;
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
	 * @return \app\models\AuthRule[]
	 */
	public function all($db = null)
	{
		return parent::all($db);
	}


	/**
	 * @inheritdoc
	 * @return \app\models\AuthRule
	 */
	public function one($db = null)
	{
		return parent::one($db);
	}
}
