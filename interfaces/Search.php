<?php


namespace app\interfaces;


use yii\data\ActiveDataProvider;

interface Search
{
    /**
     * @param \yii\db\ActiveQuery
     */
    public function updateQuery($query);

    /**
     * @param ActiveDataProvider $dataProvider
     */
    public function updateDataProvider($dataProvider);
}