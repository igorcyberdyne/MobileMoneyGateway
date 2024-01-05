<?php

namespace Ekolotech\MoMoGateway\Helper;

use Ramsey\Uuid\Uuid;

abstract class AbstractTools
{
    public static function slugify($text, string $divider = '-'): ?string
    {
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return null;
        }

        return $text;
    }

    public static function basicAuth(string $string1, string $string2): string
    {
        return base64_encode("$string1:$string2");
    }

    public static function injectVariables(string $string, array $params): string
    {
        foreach ($params as $key => $value) {
            $string = str_replace("[[$key]]", $value, $string);
        }

        return $string;
    }

    public static function uuid(): string
    {
        return Uuid::uuid4();
    }

}