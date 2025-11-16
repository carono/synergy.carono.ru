<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\search\base;

use carono\yii2helpers\QueryHelper;
use carono\yii2helpers\SortHelper;
use yii\data\ActiveDataProvider;
use yii\data\Sort;

class CommentSearch extends \app\models\Comment implements \app\interfaces\Search
{
	public function rules()
	{
		return [[['id', 'source_id', 'user_id'], 'integer'],
		[['message', 'deleted_at', 'created_at', 'updated_at'], 'safe']];
	}


	/**
	 * @param $query \yii\db\ActiveQuery
	 */
	public function updateQuery($query)
	{
		QueryHelper::regular($this, $query);
	}


	/**
	 * @param $dataProvider \yii\data\ActiveDataProvider
	 */
	public function updateDataProvider($dataProvider)
	{
		$dataProvider->sort->attributes = array_merge(SortHelper::formAttributes($this), $this->sortAttributes($dataProvider->query));
	}


	/**
	 * @param $query \yii\db\ActiveQuery
	 */
	public function sortAttributes($query)
	{
		return [];
	}


	/**
	 * @param $params array
	 * @return ActiveDataProvider
	 */
	public function updateSearch($params)
	{
		$query = self::find();
		$sort = new Sort();
		$dataProvider = new ActiveDataProvider(['query' => $query, 'sort'  => $sort]);
		$this->updateQuery($query);
		$this->updateDataProvider($dataProvider);
		return $dataProvider;
	}
}
