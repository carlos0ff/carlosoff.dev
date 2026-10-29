<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SiteContent;
use App\Services\PortfolioContent;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class AboutController extends Controller
{
    /** Caminhos aceitos para foto/currículo digitados à mão: URL ou arquivo já existente em /public. */
    private const PATH_RULE = '/^(?!.*\.\.)(https?:\/\/|image\/|docs\/|storage\/)\S+$/';

    public function edit(): View
    {
        return view('admin.about.edit', [
            'about' => array_merge(PortfolioContent::aboutDefaults(), SiteContent::read(SiteContent::ABOUT)),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $image = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'headline' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'biography' => ['nullable', 'string', 'max:10000'],
            'quote' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'string', 'max:500', 'regex:'.self::PATH_RULE],
            'photo_file' => $image,
            'remove_photo' => ['boolean'],
            'resume' => ['nullable', 'string', 'max:500', 'regex:'.self::PATH_RULE],
            'resume_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'remove_resume' => ['boolean'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'professional_status' => ['nullable', 'string', 'max:255'],
            'links' => ['nullable', 'array', 'max:10'],
            'links.*.label' => ['nullable', 'string', 'max:60'],
            'links.*.url' => ['nullable', 'url:http,https', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:320'],
            'og_image' => $image,
            'remove_og_image' => ['boolean'],
        ], [], ['photo' => 'foto', 'photo_file' => 'foto', 'resume' => 'currículo', 'resume_file' => 'currículo', 'og_image' => 'imagem OG']);

        $current = array_merge(PortfolioContent::aboutDefaults(), SiteContent::read(SiteContent::ABOUT));

        $content = collect($validated)->only([
            'name', 'headline', 'subtitle', 'description', 'biography', 'quote', 'email', 'location',
            'professional_status', 'seo_title', 'seo_description', 'og_title', 'og_description',
        ])->map(fn ($value) => $value === '' ? null : $value)->all();

        $content['photo'] = $this->file($request, 'photo_file', 'remove_photo', $current['photo'], $validated['photo'] ?? null, 'about');
        $content['resume'] = $this->file($request, 'resume_file', 'remove_resume', $current['resume'], $validated['resume'] ?? null, 'about');
        $content['og_image'] = $this->file($request, 'og_image', 'remove_og_image', $current['og_image'], $current['og_image'], 'about');
        $content['links'] = collect($validated['links'] ?? [])
            ->filter(fn (array $link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))
            ->map(fn (array $link) => ['label' => trim($link['label']), 'url' => $link['url']])
            ->values()
            ->all();

        SiteContent::write(SiteContent::ABOUT, $content);

        AuditLog::record(AuditAction::Update, 'Sobre', null, 'Página Sobre');

        return redirect()->route('admin.about.edit')->with('status', 'Página Sobre atualizada com sucesso.');
    }

    /**
     * Prioridade: novo arquivo enviado > remover > texto digitado (URL/caminho) > valor atual.
     */
    private function file(Request $request, string $fileField, string $removeField, ?string $current, ?string $typed, string $directory): ?string
    {
        if ($request->file($fileField) instanceof UploadedFile) {
            Uploads::delete($current);

            return Uploads::store($request->file($fileField), $directory);
        }

        if ($request->boolean($removeField)) {
            Uploads::delete($current);

            return null;
        }

        if ($typed !== null && $typed !== $current) {
            Uploads::delete($current);
        }

        return blank($typed) ? null : $typed;
    }
}
