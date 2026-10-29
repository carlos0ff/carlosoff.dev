<?php

namespace Database\Seeders;

use App\Enums\CourseType;
use App\Enums\TechnologyCategory;
use App\Models\Course;
use App\Models\Education;
use App\Models\Experience;
use App\Models\SiteContent;
use App\Models\Technology;
use App\Services\PortfolioContent;
use App\Support\PortfolioDates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Importa para o banco o conteúdo que antes vivia em `config/portfolio.php`
 * (experiência, formação, cursos, stack, sobre e ajustes).
 *
 * Idempotente: cada tabela só é preenchida se estiver vazia (inclusive sem itens na lixeira),
 * então nada que foi editado no painel é sobrescrito. A partir daqui a edição é feita no /admin.
 */
class PortfolioContentSeeder extends Seeder
{
    /** Categoria de cada tecnologia importada do config (ajustável no painel). */
    private const STACK_CATEGORIES = [
        'PHP' => TechnologyCategory::Languages,
        'Laravel' => TechnologyCategory::Frameworks,
        'Java' => TechnologyCategory::Languages,
        'HTML' => TechnologyCategory::Frontend,
        'CSS' => TechnologyCategory::Frontend,
        'JavaScript' => TechnologyCategory::Languages,
        'Tailwind CSS' => TechnologyCategory::Frontend,
        'MySQL' => TechnologyCategory::Database,
        'Docker' => TechnologyCategory::DevOps,
        'GitHub' => TechnologyCategory::Tools,
    ];

    public function run(): void
    {
        $this->importExperiences();
        $this->importEducation();
        $this->importTechnologies();
        $this->importCourses();
        $this->importAbout();
        $this->importSettings();

        PortfolioContent::flush();
    }

    private function importExperiences(): void
    {
        if (Experience::withTrashed()->exists()) {
            return;
        }

        foreach (config('portfolio.experiences', []) as $index => $item) {
            $period = $this->parsePeriod($item['period'] ?? '');

            Experience::create([
                'role' => $item['role'],
                'company' => $item['company'],
                'description' => $item['description'] ?? null,
                'start_date' => $period['start'],
                'end_date' => $period['end'],
                'is_current' => $period['current'] || ($item['current'] ?? false),
                'period_note' => $period['note'],
                'technologies' => $item['skills'] ?? [],
                'logo' => $item['logo'] ?? null,
                'url' => $item['url'] ?? null,
                'is_published' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function importEducation(): void
    {
        if (Education::withTrashed()->exists()) {
            return;
        }

        foreach (config('portfolio.education', []) as $index => $item) {
            $period = $this->parsePeriod($item['period'] ?? '');

            Education::create([
                'course' => $item['role'],
                'institution' => $item['company'],
                'description' => $item['description'] ?? null,
                'start_date' => $period['start'],
                'end_date' => $period['end'],
                'in_progress' => $period['current'] || ($item['current'] ?? false),
                'period_note' => $period['note'],
                'skills' => $item['skills'] ?? [],
                'logo' => $item['logo'] ?? null,
                'url' => $item['url'] ?? null,
                'is_published' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function importTechnologies(): void
    {
        if (Technology::withTrashed()->exists()) {
            return;
        }

        foreach (config('portfolio.stack', []) as $index => $item) {
            Technology::create([
                'name' => $item['name'],
                'category' => (self::STACK_CATEGORIES[$item['name']] ?? TechnologyCategory::Other)->value,
                'icon' => $item['icon'] ?? null,
                'invert_icon_in_dark' => $item['invert'] ?? false,
                'is_published' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function importCourses(): void
    {
        if (Course::withTrashed()->exists()) {
            return;
        }

        foreach (config('portfolio.certifications', []) as $index => $item) {
            Course::create([
                'name' => $item['title'],
                'institution' => $item['issuer'],
                'type' => CourseType::Course->value,
                'description' => $item['description'] ?? null,
                'image' => $item['file'] ?? null,
                'logo' => $item['logo'] ?? null,
                'is_published' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function importAbout(): void
    {
        if (SiteContent::where('key', SiteContent::ABOUT)->exists()) {
            return;
        }

        $about = config('portfolio.about', []);

        SiteContent::write(SiteContent::ABOUT, [
            'name' => $about['name'] ?? '',
            'headline' => $about['headline'] ?? '',
            'biography' => implode("\n\n", $about['paragraphs'] ?? []),
            'quote' => $about['quote'] ?? null,
            'photo' => $about['photo'] ?? null,
            'resume' => $about['resume'] ?? null,
            'email' => $about['email'] ?? null,
        ]);
    }

    private function importSettings(): void
    {
        if (SiteContent::where('key', SiteContent::SETTINGS)->exists()) {
            return;
        }

        SiteContent::write(SiteContent::SETTINGS, PortfolioContent::settingsDefaults());
    }

    /**
     * "Set 2026 – Presente", "Jun 2021 – Ago 2025", "Fev 2023 – Dez 2025 · Trancada".
     *
     * @return array{start: ?Carbon, end: ?Carbon, current: bool, note: ?string}
     */
    private function parsePeriod(string $text): array
    {
        [$range, $note] = array_pad(array_map('trim', explode('·', $text, 2)), 2, null);
        [$from, $to] = array_pad(array_map('trim', explode('–', (string) $range, 2)), 2, '');

        return [
            'start' => PortfolioDates::parseMonthYear($from),
            'end' => PortfolioDates::parseMonthYear($to),
            'current' => mb_strtolower($to) === 'presente',
            'note' => $note ?: null,
        ];
    }
}
