<?php

namespace App\Utils;

abstract class StringHelper
{
    public static function stringsContains(array $words, string $sentence): bool
    {
        foreach ($words as $word) {
            if (str_contains($sentence, $word)) {
                return true;
            }
        }
        return false;
    }
}
