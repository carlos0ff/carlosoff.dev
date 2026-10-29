<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Identificador público (URLs, API, portal) que não expõe o id sequencial.
 */
trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function (self $model): void {
            $model->uuid ??= (string) Str::uuid();
        });
    }
}
