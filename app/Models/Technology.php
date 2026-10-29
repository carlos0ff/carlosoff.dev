<?php

namespace App\Models;

use App\Enums\TechnologyCategory;
use App\Models\Concerns\HasPortfolioOrdering;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'slug', 'category', 'description', 'icon', 'invert_icon_in_dark', 'official_url', 'level',
    'years_experience', 'featured', 'is_published', 'sort_order',
])]
class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory, HasPortfolioOrdering;

    public const UPLOAD_DIRECTORY = 'technologies';

    protected static function booted(): void
    {
        static::creating(function (Technology $technology): void {
            $technology->slug = filled($technology->slug) ? $technology->slug : static::uniqueSlug($technology->name);
        });
    }

    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tecnologia';
        $slug = $base;

        for ($suffix = 2; static::withTrashed()->where('slug', $slug)->exists(); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => TechnologyCategory::class,
            'invert_icon_in_dark' => 'boolean',
            'years_experience' => 'integer',
            'featured' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'invert' => $this->invert_icon_in_dark,
            'category' => $this->category->label(),
            'description' => $this->description,
            'url' => $this->official_url,
            'level' => $this->level,
            'years_experience' => $this->years_experience,
            'featured' => $this->featured,
        ];
    }
}
