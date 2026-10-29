<?php

namespace App\Models;

use App\Enums\CourseType;
use App\Models\Concerns\HasPortfolioOrdering;
use App\Support\PortfolioDates;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'institution', 'type', 'description', 'issued_at', 'completed_at', 'workload_hours',
    'certificate_url', 'credential', 'image', 'logo', 'is_published', 'sort_order',
])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, HasPortfolioOrdering;

    public const UPLOAD_DIRECTORY = 'courses';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CourseType::class,
            'issued_at' => 'date',
            'completed_at' => 'date',
            'workload_hours' => 'integer',
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
            'title' => $this->name,
            'issuer' => $this->institution,
            'type' => $this->type->label(),
            'date' => PortfolioDates::monthYear($this->completed_at ?? $this->issued_at),
            'description' => $this->description,
            'file' => $this->image,
            'logo' => $this->logo,
            'certificate_url' => $this->certificate_url,
            'credential' => $this->credential,
            'workload_hours' => $this->workload_hours,
        ];
    }
}
