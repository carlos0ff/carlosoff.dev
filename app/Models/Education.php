<?php

namespace App\Models;

use App\Models\Concerns\HasPortfolioOrdering;
use App\Support\PortfolioDates;
use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'course', 'institution', 'degree', 'description', 'start_date', 'end_date', 'in_progress', 'period_note',
    'location', 'skills', 'logo', 'url', 'is_published', 'sort_order',
])]
class Education extends Model
{
    /** @use HasFactory<EducationFactory> */
    use HasFactory, HasPortfolioOrdering;

    public const UPLOAD_DIRECTORY = 'education';

    protected $table = 'education';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'in_progress' => 'boolean',
            'skills' => 'array',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return Attribute<string|null, never> */
    protected function periodLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => PortfolioDates::period($this->start_date, $this->end_date, $this->in_progress, $this->period_note));
    }

    /** "Bacharelado, Computer Science" (curso) + grau, quando informado separadamente. */
    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => filled($this->degree) ? $this->course.', '.$this->degree : $this->course);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'company' => $this->institution,
            'role' => $this->title,
            'period' => $this->period_label,
            'current' => $this->in_progress,
            'description' => $this->description,
            'skills' => $this->skills ?? [],
            'logo' => $this->logo,
            'url' => $this->url,
            'location' => $this->location,
        ];
    }
}
