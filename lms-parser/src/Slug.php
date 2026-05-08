<?php

declare(strict_types=1);

namespace Carono\LmsParser;

final class Slug
{
    private const TR = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z',
        'и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r',
        'с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch',
        'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];

    public static function make(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, self::TR);
        $text = preg_replace('~[^a-z0-9._-]+~u', '_', $text) ?? $text;
        $text = preg_replace('~_+~', '_', $text) ?? $text;
        $text = trim($text, '_');
        return $text === '' ? 'item' : substr($text, 0, 100);
    }
}
