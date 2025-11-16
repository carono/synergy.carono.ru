<?php

namespace app\helpers;

use app\modules\first\forms\Case1Form;
use ReflectionMethod;
use Yii;
use yii\helpers\Html;

class CodeHelper
{
    public static function outSource($class, $method)
    {
        $source = CodeHelper::outGitHubLink($class);
        $code = self::getClassMethodSourceCode($class, $method);

        return <<<HTML
<div>Исходный код на github: $source</div>
<pre class=\"language-php\"><code>{$code}</code></pre>
HTML;
    }

    public static function getClassMethodSourceCode(string $className, string $methodName): ?string
    {
        if (!class_exists($className)) {
            throw new \Exception("Class $className not found after including file.");
        }

        $reflection = new ReflectionMethod($className, $methodName);

        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();

        if (!$start || !$end) {
            return null;
        }
        $filePath = self::getClassFilePath($className);
        $length = $end - $start + 1;
        $fileLines = file($filePath);

        // Извлекаем нужные строки
        $methodLines = array_slice($fileLines, $start - 1, $length);

        return implode("", $methodLines);
    }

    public static function getClassFilePath(string $class): ?string
    {
        $file = $class;
        $file = str_replace('\\', '/', $file);
        return Yii::getAlias('@' . $file) . '.php';
    }

    public static function outGitHubLink($class, $caption = null)
    {
        $file = $class;
        $file = str_replace('\\', '/', $file);
        $url = str_replace('app/', 'https://github.com/carono/synergy.carono.ru/blob/master/', $file) . '.php';
        $caption = $caption ?:  str_replace('app/', '', $file);
        return Html::a($caption, $url, ['target' => '_blank']);
    }
}