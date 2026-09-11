<?php

declare(strict_types=1);

namespace App\Helpers;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Write a record under a slug nothing else holds. Reading which slugs are taken
 * and writing the row are two statements, so two people a moment apart can both
 * read the same slug as free. The unique index has the last word, and the one
 * that loses the race is written again under the next number.
 */
class Slug
{
    private const int ATTEMPTS = 5;

    /**
     * @template TModel of Model
     *
     * @param  Closure(string): bool  $taken
     * @param  Closure(string): TModel  $write
     * @return TModel
     */
    public static function write(string $name, string $fallback, Closure $taken, Closure $write): Model
    {
        $base = Str::slug($name);
        $base = $base === '' ? $fallback : $base;

        for ($attempt = 1; $attempt < self::ATTEMPTS; $attempt++) {
            try {
                return self::attempt($base, $taken, $write);
            } catch (UniqueConstraintViolationException) {
                // The row that won the race is visible now, so the next look
                // lands on the next number.
            }
        }

        return self::attempt($base, $taken, $write);
    }

    /**
     * The write has a transaction of its own so that a refused row leaves
     * nothing behind: Postgres abandons everything written since the
     * transaction began otherwise, and the action calling this has one open.
     *
     * @template TModel of Model
     *
     * @param  Closure(string): bool  $taken
     * @param  Closure(string): TModel  $write
     * @return TModel
     */
    private static function attempt(string $base, Closure $taken, Closure $write): Model
    {
        return DB::transaction(fn (): Model => $write(self::free($base, $taken)));
    }

    /** @param  Closure(string): bool  $taken */
    private static function free(string $base, Closure $taken): string
    {
        $slug = $base;
        $suffix = 0;

        while ($taken($slug)) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
