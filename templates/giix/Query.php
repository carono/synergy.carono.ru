<?php


namespace app\templates\giix;


use Nette\PhpGenerator\Method;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\data\Sort;
use yii\helpers\ArrayHelper;

class Query extends \carono\giix\templates\model\Query
{
    /**
     * @param Method $method
     * @return bool
     */
    public function notDeleted($method)
    {
        $columns = $this->params['tableSchema']->columns;
        $tableName = $this->params['tableName'];
        if (isset($columns['deleted_at'])) {
            $method->addComment('@return $this');
            $code = <<<PHP
return \$this->andWhere(['{{%$tableName}}.[[deleted_at]]' => null]);
PHP;
            $method->addBody($code);
        } else {
            return false;
        }
    }

    /**
     * @param Method $method
     * @return bool
     */
    public function byCompanies($method)
    {
        $columns = $this->params['tableSchema']->columns;
        $tableName = $this->params['tableName'];
        if (!isset($columns['company_id'])) {
            return false;
        }
        $body = <<<PHP
  return \$this->andWhere(['{{%$tableName}}.[[company_id]]' => \$ids ?: \Yii::\$app->user->identity->getAvailableCompanyQuery()->select(['id'])]);
PHP;

        $method->addParameter('ids', []);
        $method->addBody($body);
        $method->addComment('@param array|integer $ids');
        $method->addComment('@return $this');
    }

    public function isActive($method)
    {
        $columns = $this->params['tableSchema']->columns;
        $tableName = $this->params['tableName'];
        if (isset($columns['is_active'])) {
            $method->addComment('@return $this');
            $code = <<<PHP
return \$this->andWhere(['{{%$tableName}}.[[is_active]]' => true]);
PHP;
            $method->addBody($code);
        } else {
            return false;
        }
    }

    /**
     * @param Method $method
     */
    public function available($method)
    {
        $columns = $this->params['tableSchema']->columns;
        $method->addComment('@return $this');
        $chain = [];
        if (isset($columns['deleted_at'])) {
            $chain[] = 'notDeleted()';
        }
        if (isset($columns['company_id'])) {
            $chain[] = 'byCompanies()';
        }
        if (isset($columns['is_active'])) {
            $chain[] = 'isActive()';
        }
        $code = implode('->', $chain);
        $code = $code ? '->' . $code : $code;

        $php = <<<PHP
    return \$this$code;
PHP;

        $method->addBody($php);
    }

    public function filter($method)
    {
        $method->addComment('@var array|\yii\db\ActiveRecord $model');
        $method->addComment('@return $this');
        $method->addParameter('model', null);
        $this->phpNamespace->addUse('carono\yii2helpers\QueryHelper');
        $regular = <<<PHP
if (\$model instanceof \app\interfaces\Search){
    \$model->updateQuery(\$this);
} elseif (\$model instanceof \yii\db\ActiveRecord){
    QueryHelper::regular(\$model, \$this);
}
PHP;
        $method->addBody($regular);
        $method->addBody('return $this;');
    }

    public function search($method)
    {
        $method->addComment('@var mixed $filter');
        $method->addComment('@var array $options Options for ActiveDataProvider');
        $method->addParameter('filter', null);
        $method->addParameter('options', []);
        $method->addComment("@return ActiveDataProvider");
        $body = <<<PHP
\$class = \yii\helpers\ArrayHelper::remove(\$options, 'class', ActiveDataProvider::class);
\$sort = new Sort();

\$query = clone \$this;
if (method_exists(\$query, 'filter')) {
    \$query->filter(\$filter);
}

if (\$query->asArray) {
    \$class = \yii\data\ArrayDataProvider::class;
    \$options['allModels'] = \$query->all();
} else {
    \$options['query'] = \$query;
}

\$options['sort'] = \$sort;
\$options['class'] = \$class;

return \Yii::createObject(\$options);
PHP;
        $method->addBody($body);
    }
}