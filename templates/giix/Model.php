<?php


namespace app\templates\giix;


use Nette\PhpGenerator\Method;

class Model extends \carono\giix\templates\model\Model
{

    /**
     * @param Method $method
     * @return bool
     */
    public function behaviors($method)
    {
        $behaviors = [];
        $columns = $this->params['tableSchema']->columns;
        if (isset($columns['created_at']) || isset($columns['updated_at'])) {
            $created = isset($columns['created_at']) ? "'created_at'" : 'null';
            $updated = isset($columns['updated_at']) ? "'updated_at'" : 'null';

            $column = $columns['created_at'] ?? $columns['updated_at'];


            if ($column->type == 'integer') {
                $value = '';
            } else {
                $value = "'value' => new \yii\db\Expression('NOW()'),";
            }

            $behaviors[] = <<<PHP
    'timestamp' => [
        'class' => 'yii\behaviors\TimestampBehavior',
        $value
        'createdAtAttribute' => $created,
        'updatedAtAttribute' => $updated
    ]
PHP;
        }

        if (isset($columns['deleted_at'])) {
            $behaviors[] = <<<PHP
    'softDeleteBehavior' => [
        'class' => 'yii2tech\ar\softdelete\SoftDeleteBehavior',
        'softDeleteAttributeValues' => [
            'deleted_at' => new \yii\db\Expression('NOW()'),
        ],
        'restoreAttributeValues'=> ['deleted_at' => null],
        'replaceRegularDelete' => true
    ]
PHP;
        }

        if ($behaviors) {
            $behaviorsString = implode(",\n", $behaviors);
            $php = <<<PHP
return array_merge(\app\helpers\BehaviorHelper::getBehaviorConfigures(self::class), [
$behaviorsString
]);
PHP;
            $method->addBody($php);
            return true;
        }
        return false;
    }

    /**
     * @param Method $method
     */
    public function rules($method)
    {
        $method->addComment('@inheritdoc');
        $rules = str_replace('className()', 'class', $this->params['rules']);
        $trim = [];
        foreach ($this->params['tableSchema']->columns as $column => $schema) {
            if (in_array($schema->type, ['string', 'text'])) {
                $trim[] = "'$column'";
            }
        }
        if ($trim) {
            $rules[] = '[[' . implode(', ', $trim) . "], 'trim']";
        }
        $str = implode(",\n      ", $rules);

        $method->addBody("return [\n$str\n];");
    }
}