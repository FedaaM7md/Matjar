<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Slug
{
    public static function unique(string $modelClass, string $source, ?int $ignoreId = null, string $fallback = 'item'): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = $fallback;
        }

        $slug = $base;
        $counter = 2;

        while (self::exists($modelClass, $slug, $ignoreId)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /** @param class-string<Model> $modelClass */
    private static function exists(string $modelClass, string $slug, ?int $ignoreId): bool
    {
        $query = $modelClass::query()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
