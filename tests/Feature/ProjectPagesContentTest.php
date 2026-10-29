<?php

namespace Tests\Feature;

use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectPagesContentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function projectPages(): array
    {
        return [
            'currencee' => ['currencee', 'Currencee'],
            'fintrack' => ['fintrack', 'FinTrack'],
            'workfacilit' => ['workfacilit', 'WorkFacilit'],
            'vigiasus' => ['vigiasus', 'VigiaSUS'],
            'picpay-wallet' => ['picpay-wallet', 'PicPay Wallet'],
        ];
    }

    #[DataProvider('projectPages')]
    public function test_every_project_page_has_seo_breadcrumb_and_no_invented_links(string $slug, string $title): void
    {
        $this->seed(ProjectSeeder::class);

        $response = $this->get("/projetos/{$slug}")->assertOk();

        $response->assertSee("<title>{$title} — Projetos</title>", false);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<meta property="og:title" content="'.$title.' — Projetos">', false);
        $response->assertSee('<link rel="canonical" href="'.url("/projetos/{$slug}").'">', false);
        $response->assertSee('aria-label="Você está em"', false);
        $response->assertSee('Visão geral');
        $response->assertSee('Tecnologias');
        $response->assertSee('Screenshots');

        preg_match('/<article class="mx-auto.*?<\/article>/s', $response->getContent(), $article);
        $this->assertStringNotContainsString('target="_blank"', $article[0]);
        $this->assertStringNotContainsString('http', str_replace(url('/'), '', $article[0]));
    }

    public function test_fintrack_page_describes_the_real_stack_and_uses_existing_screenshots(): void
    {
        $this->seed(ProjectSeeder::class);

        $response = $this->get('/projetos/fintrack')->assertOk();

        $response->assertSeeInOrder(['Visão geral', 'Tecnologias', 'Funcionalidades', 'Arquitetura', 'Integração com n8n e WhatsApp', 'Banco de dados e infraestrutura', 'Screenshots', 'Desenvolvimento e desafios', 'Conclusão']);
        $response->assertSee('Inertia.js');
        $response->assertSee('X-N8N-Secret');
        $response->assertSee('marcadas como TODO');
        $response->assertDontSee('data-screenshots-empty', false);

        foreach (['Dashboard', 'contas', 'chatbot'] as $screenshot) {
            $this->assertFileExists(public_path("image/projetos/organizze/{$screenshot}.png"));
            $response->assertSee(asset("image/projetos/organizze/{$screenshot}.png"), false);
        }
    }

    public function test_vigiasus_page_shows_the_implemented_pipeline_and_flags_n8n_as_planned(): void
    {
        $this->seed(ProjectSeeder::class);

        $response = $this->get('/projetos/vigiasus')->assertOk();

        $response->assertSeeInOrder(['Arquitetura', 'Pipeline de dados', 'Ingestão de dados', 'PostgreSQL', 'Neo4j', 'n8n', 'Desenvolvimento e desafios', 'Conclusão']);
        $response->assertSeeInOrder(['Fontes públicas', 'Spring Boot: clientes das APIs', 'Processamento: detecção de alertas', 'Dados organizados']);

        // 12 etapas: 6 do fluxo implementado + 6 da proposta com n8n; só as da proposta futura ficam tracejadas.
        $this->assertSame(12, substr_count($response->getContent(), 'data-flow-step'));
        $this->assertSame(2, substr_count($response->getContent(), 'data-flow-planned'));
        $response->assertSee('ainda não foi implementado');
        $response->assertSee('data-screenshots-empty', false);
    }

    #[DataProvider('leanPages')]
    public function test_projects_without_source_code_only_state_what_is_documented(string $slug): void
    {
        $this->seed(ProjectSeeder::class);

        $response = $this->get("/projetos/{$slug}")->assertOk();

        $response->assertSee('serão detalhados aqui quando a documentação do projeto for publicada');
        $response->assertDontSee('Funcionalidades');
        $response->assertDontSee('Desafio');
        $response->assertSee('data-screenshots-empty', false);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function leanPages(): array
    {
        return ['workfacilit' => ['workfacilit'], 'picpay-wallet' => ['picpay-wallet']];
    }
}
