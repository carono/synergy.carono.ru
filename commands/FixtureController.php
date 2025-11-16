<?php


namespace app\commands;


use yii\db\ActiveRecord;
use yii\helpers\Console;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;
use yii\test\ActiveFixture;
use function strtotime;

class FixtureController extends \yii\console\controllers\FixtureController
{
    public function actionDump()
    {
        /**
         * @var ActiveFixture $fixture
         * @var ActiveRecord $modelClass
         */
        $fixturesPath = \Yii::getAlias('@app/fixtures');
        $filesToSearch = ['*Fixture.php'];
        if (!is_dir($fixturesPath)) {
            FileHelper::createDirectory(basename($fixturesPath));
        }
        $files = FileHelper::findFiles($fixturesPath, ['only' => $filesToSearch]);
        $foundFixtures = [];

        foreach ($files as $fixtureFile) {
            $foundFixtures[] = 'app\fixtures\\' . basename($fixtureFile, 'Fixture.php') . 'Fixture';
        }

        foreach ($foundFixtures as $class) {
            $fixture = new $class;
            if (str_contains($fixture->dataFile, 'empty')) {
                continue;
            }
            $modelClass = $fixture->modelClass;
            $query = $modelClass::find()->asArray();
            $this->updateQuery($query, $fixture);
            $models = $query->all();
            $records = $this->filterRecords($models, $fixture);

            $class = new \ReflectionClass($fixture);
            $dataFile = FileHelper::normalizePath(dirname($class->getFileName()) . '/data/' . $fixture->getTableSchema()->fullName . '.php');

            $php = VarDumper::export($records);
            Console::output($dataFile);
            file_put_contents($dataFile, "<?php" . PHP_EOL . PHP_EOL . "return " . $php . ';');
        }

        return $foundFixtures;
    }


    public function filterRecords($records, ActiveFixture $fixture)
    {
        return array_map(function ($model) use ($fixture) {
            return $this->prepareRecord($model, $fixture);
        }, $records);
    }

    public function prepareRecord($record, ActiveFixture $fixture)
    {
        $record = array_filter($record, function ($value) use ($fixture) {
            return $value !== null;
        });

        $json = [];
        foreach ($fixture->getTableSchema()->getColumnNames() as $column) {
            if ($fixture->getTableSchema()->getColumn($column)->type == 'json') {
                $json[$column] = $column;
            }
        }

        foreach ($record as $key => $value) {
            if (in_array($key, ['created_at', 'updated_at'])) {
                $date = date('Y-01-01 00:00:00');
                if ($fixture->tableSchema->getColumn($key)->type == 'integer') {
                    $record[$key] = strtotime($date);
                } else {
                    $record[$key] = $date;
                }
            }
        }

        foreach (array_intersect_key($record, $json) as $key => $value) {
            $record[$key] = json_decode($value, true);
        }

        return $record;
    }

    public function updateQuery($query, $fixture)
    {
        if (method_exists($query, 'notDeleted')) {
            $query->notDeleted();
        }
    }
}