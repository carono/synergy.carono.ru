<?php

/**
 * This class is generated using the package carono/codegen
 */

namespace app\models\search;

class LmsDisciplineSearch extends base\LmsDisciplineSearch
{
	public function rules()
	{
		return array_merge(parent::rules(), []);
	}

	public function updateDataProvider($dataProvider)
	{
		parent::updateDataProvider($dataProvider);
		$dataProvider->sort->defaultOrder = ['semester' => SORT_DESC];
	}
}
