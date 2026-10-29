<?php

namespace App\Models;

use App\Models\Concerns\HasPortfolioOrdering;
use App\Support\PortfolioDates;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'role', 'company', 'description', 'start_date', 'end_date', 'is_current', 'period_note', 'location',
    'technologies', 'responsibilities', 'results', 'logo', 'url', 'is_published', 'sort_order',
])]
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory, HasPortfolioOrdering;

    public const UPLOAD_DIRECTORY = 'experiences';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
            'technologies' => 'array',
            'responsibilities' => 'array',
            'results' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return Attribute<string|null, never> */
    protected function periodLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => PortfolioDates::period($this->start_date, $this->end_date, $this->is_current, $this->period_note));
    }

    /**
     * Mesmo formato que os componentes públicos já consumiam quando o conteúdo vinha do config.
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'company' => $this->company,
            'role' => $this->role,
            'period' => $this->period_label,
            'current' => $this->is_current,
            'description' => $this->description,
            'logo' => $this->logo,
            'url' => $this->url,
            'skills' => $this->technologies ?? [],
            'location' => $this->location,
            'responsibilities' => $this->responsibilities ?? [],
            'results' => $this->results ?? [],
        ];
    }
}
