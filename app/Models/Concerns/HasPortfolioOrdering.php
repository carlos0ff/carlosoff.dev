<?php

namespace App\Models\Concerns;

use App\Services\PortfolioContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comportamento comum dos conteúdos do portfólio: publicado/rascunho, ordem manual,
 * exclusão lógica e invalidação do cache público sempre que algo muda.
 */
trait HasPortfolioOrdering
{
    use SoftDeletes;

    protected static function bootHasPortfolioOrdering(): void
    {
        $flush = fn () => PortfolioContent::flush();

        static::saved($flush);
        static::deleted($flush);
        static::restored($flush);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public static function nextSortOrder(): int
    {
        return (int) static::withTrashed()->max('sort_order') + 1;
    }
}
