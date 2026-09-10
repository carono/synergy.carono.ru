<?php

declare(strict_types=1);

namespace Carono\LmsParser;

/**
 * Маскировка чувствительных значений для вывода и логов.
 *
 * Прогон парсера пишется в лог и попадает в отчёты, поэтому логин LMS не должен
 * появляться в нём целиком: `./bin/parse ... >> logs/parse.log` иначе оставляет
 * учётку в файле, который легко переслать или закоммитить.
 */
final class Secret
{
    /**
     * info@example.ru → i***@example.ru
     */
    public static function maskLogin(string $login): string
    {
        if ($login === '') {
            return '';
        }

        $at = strpos($login, '@');
        if ($at === false) {
            return self::maskTail($login);
        }

        return self::maskTail(substr($login, 0, $at)).substr($login, $at);
    }

    private static function maskTail(string $value): string
    {
        return mb_substr($value, 0, 1).'***';
    }
}
