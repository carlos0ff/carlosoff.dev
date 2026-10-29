<?php

namespace App\Enums;

enum TechnologyCategory: string
{
    case Frontend = 'frontend';
    case Backend = 'backend';
    case Database = 'database';
    case DevOps = 'devops';
    case Cloud = 'cloud';
    case Languages = 'languages';
    case Frameworks = 'frameworks';
    case Tools = 'tools';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Frontend => 'Frontend',
            self::Backend => 'Backend',
            self::Database => 'Database',
            self::DevOps => 'DevOps',
            self::Cloud => 'Cloud',
            self::Languages => 'Languages',
            self::Frameworks => 'Frameworks',
            self::Tools => 'Tools',
            self::Other => 'Other',
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
