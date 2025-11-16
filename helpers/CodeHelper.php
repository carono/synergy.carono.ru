<?php

namespace app\helpers;

use ReflectionClass;
use ReflectionMethod;
use Yii;
use yii\helpers\Html;

class CodeHelper
{
    public static function outSourceFile($file)
    {
        $filePath = Yii::getAlias($file);
        $source = CodeHelper::outGitHubLink($file);
        $code = htmlspecialchars(file_get_contents($filePath));
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return <<<HTML
<div>Исходный код файла $source</div>
<pre class="language-{$extension}"><code>{$code}</code></pre>
HTML;
    }

    public static function outSource($object, $method = null)
    {
        $source = CodeHelper::outGitHubLink($object);

        if ($method) {
            $code = self::getClassMethodSourceCode($object, $method);
            $title = "Исходный код метода на github:";
        } else {
            $code = self::getClassSourceCode($object);
            $title = "Исходный код класса на github:";
        }

        $code = htmlspecialchars($code);

        return <<<HTML
<div>$title $source</div>
<pre class="language-php"><code>{$code}</code></pre>
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

    public static function getClassSourceCode(string $className): ?string
    {
        $filePath = self::getClassFilePath($className);

        if (!file_exists($filePath)) {
            throw new \Exception("File for class $className not found.");
        }

        return file_get_contents($filePath);
    }

    public static function getClassFilePath(string $class): ?string
    {
        $reflection = new ReflectionClass($class);
        return $reflection->getFileName();
    }

    public static function outGitHubLink($object, $caption = null)
    {
        if (file_exists(Yii::getAlias($object))) {
            $file = Yii::getAlias($object);
            $url = str_replace(Yii::getAlias('@app'), 'https://github.com/carono/synergy.carono.ru/blob/master', $file);
            $caption = str_replace(Yii::getAlias('@app'), '', $file);
        } else {
            $file = $object;
            $file = str_replace('\\', '/', $file);
            $url = str_replace('app/', 'https://github.com/carono/synergy.carono.ru/blob/master/', $file) . '.php';
            $caption = $caption ?: str_replace('app/', '', $file);
        }
        return Html::a($caption, $url, ['target' => '_blank']);
    }
}