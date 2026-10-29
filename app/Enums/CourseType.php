<?php

namespace App\Enums;

enum CourseType: string
{
    case Course = 'course';
    case Certification = 'certification';
    case Workshop = 'workshop';
    case Bootcamp = 'bootcamp';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Course => 'Curso',
            self::Certification => 'Certificação',
            self::Workshop => 'Workshop',
            self::Bootcamp => 'Bootcamp',
            self::Other => 'Outro',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
