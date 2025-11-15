<?php

namespace app\helpers;

use ReflectionMethod;
use Yii;
use yii\helpers\Html;

class CodeHelper
{
    public static function outSource($class, $method)
    {
        $code = self::getClassMethodSourceCode($class, $method);
        return "<pre class=\"language-php\"><code>{$code}</code></pre>";
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

    public static function outGitHubLink($class, $caption = 'Исходный код')
    {

        return Html::a($caption, $url, ['target' => '_blank']);
    }
}