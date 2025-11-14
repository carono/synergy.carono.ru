<?php


namespace app\templates\giix;


use carono\giix\ClassGenerator;
use Nette\PhpGenerator\Method;
use yii\gii\generators\crud\Generator;

class SearchBase extends ClassGenerator
{
    protected function classUses()
    {
        return [
            'yii\data\Sort',
            'yii\data\ActiveDataProvider',
            'carono\yii2helpers\SortHelper'
        ];
    }

    protected function classImplements()
    {
        return [
            '\app\interfaces\Search'
        ];
    }

    protected function formExtends()
    {
        return $this->params['ns'] . '\\' . $this->params['className'];
    }

    protected function formClassName()
    {
        return $this->params['className'] . 'Search';
    }

    protected function formOutputPath()
    {
        return \Yii::getAlias('@app/models/search/base/' . $this->formClassName() . '.php');
    }

    protected function formClassNamespace()
    {
        return 'app\models\search\base';
    }

    /**
     * @param Method $method
     */
    public function rules($method)
    {
        $body = 'return [' . implode(',' . PHP_EOL, $this->generateSearchRules() ?: []) . '];';
        $method->addBody($body);
    }

    protected function generateSearchRules()
    {
        if (class_exists($this->formExtends())) {
            $generator = new Generator();
            $generator->modelClass = $this->formExtends();
            return $generator->generateSearchRules();
        }
    }


    /**
     * @param Method $method
     */
    public function updateQuery($method)
    {
        $method->addParameter('query');
        $method->addComment('@param $query \yii\db\ActiveQuery');
        $this->phpNamespace->addUse('carono\yii2helpers\QueryHelper');
        $method->addBody('QueryHelper::regular($this, $query);');
    }

    /**
     * @param Method $method
     */
    public function updateDataProvider($method)
    {
        $method->addParameter('dataProvider');
        $method->addComment('@param $dataProvider \yii\data\ActiveDataProvider');
        $method->addBody('$dataProvider->sort->attributes = array_merge(SortHelper::formAttributes($this), $this->sortAttributes($dataProvider->query));');
    }

    /**
     * @param Method $method
     */
    public function sortAttributes($method)
    {
        $method->addParameter('query');
        $method->addComment('@param $query \yii\db\ActiveQuery');
        $method->addBody('return [];');
    }

    /**
     * @param Method $method
     */
    public function updateSearch($method)
    {
        $method->addParameter('params');
        $method->addComment('@param $params array');
        $method->addComment('@return ActiveDataProvider');
        $body = <<<PHP
\$query = self::find();
\$sort = new Sort();
\$dataProvider = new ActiveDataProvider(['query' => \$query, 'sort'  => \$sort]);
\$this->updateQuery(\$query);
\$this->updateDataProvider(\$dataProvider);
return \$dataProvider;
PHP;
        $method->addBody($body);
    }
}