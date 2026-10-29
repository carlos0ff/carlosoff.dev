<?php

namespace App\Models;

use App\Services\PortfolioContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Conteúdo "avulso" do site em pares chave/valor JSON (ex.: `about`, `settings`).
 */
#[Fillable(['key', 'value'])]
class SiteContent extends Model
{
    public const ABOUT = 'about';

    public const SETTINGS = 'settings';

    protected static function booted(): void
    {
        static::saved(fn () => PortfolioContent::flush());
        static::deleted(fn () => PortfolioContent::flush());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function read(string $key): array
    {
        return static::where('key', $key)->first()?->value ?? [];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function write(string $key, array $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
