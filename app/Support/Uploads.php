<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Arquivos enviados pelo painel ficam no disco `public` e são gravados no banco como
 * `storage/<pasta>/<arquivo>` (relativo a /public, igual às imagens estáticas `image/...`),
 * então `asset($path)` funciona para os dois casos. Só arquivos enviados podem ser apagados.
 */
class Uploads
{
    private const PREFIX = 'storage/';

    public static function store(UploadedFile $file, string $directory): string
    {
        return self::PREFIX.$file->store($directory, 'public');
    }

    public static function isUploaded(?string $path): bool
    {
        return $path !== null && Str::startsWith($path, self::PREFIX);
    }

    public static function delete(?string $path): void
    {
        if (self::isUploaded($path)) {
            Storage::disk('public')->delete(Str::after($path, self::PREFIX));
        }
    }
}
