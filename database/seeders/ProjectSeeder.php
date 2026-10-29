<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Projetos iniciais do portfólio. Idempotente: pode rodar várias vezes sem duplicar
     * nem sobrescrever o que foi editado no painel (só preenche `details` quando ainda está vazio).
     * `github_url` e `demo_url` ficam `null` até serem informados (nada de URLs inventadas).
     */
    public function run(): void
    {
        $projects = [
            [
                'title' => 'Currencee',
                'slug' => 'currencee',
                'description' => 'Aplicação web de conversão de moedas em tempo real, com suporte a mais de 160 moedas, interface moderna e responsiva.',
                'image' => null,
                'technologies' => ['React', 'Vite', 'Node.js', 'JavaScript', 'CSS', 'Express', 'API de moedas'],
                'featured' => false,
                'details' => [
                    'overview' => [
                        'Currencee é um conversor de moedas prático e confiável, pensado para tornar a conversão entre moedas rápida e sem complicação. É possível converter entre as principais moedas do mundo, como USD, GBP, EUR e INR, entre muitas outras.',
                        'Os dados de câmbio vêm da CurrencyBeacon API, que cobre mais de 160 moedas ao redor do mundo.',
                    ],
                    'features' => [
                        'Mais de 160 moedas disponíveis para conversão.',
                        'Conversão em tempo real por meio de uma API de câmbio.',
                        'Interface moderna e responsiva, construída com React e CSS.',
                        'Proxy de API com Node.js/Express que protege as chaves de acesso e expõe os endpoints de dados de moedas.',
                        'Compatibilidade entre navegadores e dispositivos.',
                    ],
                    'development' => [
                        'A interface foi construída com React (Vite) e CSS puro. Um servidor Node.js com Express funciona como proxy entre a interface e a API de câmbio, mantendo a chave de API protegida fora do código do navegador.',
                    ],
                    'challenges' => [
                        [
                            'title' => 'Compatibilidade com versões antigas do Safari',
                            'problem' => 'O layout inicial usava CSS Grid, sem suporte nas versões mais antigas do Safari (3.2 a 10.2). Mesmo após a troca para Flexbox, a responsividade continuava quebrada nesses navegadores, porque faltavam prefixos de fornecedor (vendor prefixes) no CSS.',
                            'solution' => 'Foi adotado o Autoprefixer, que gera automaticamente os prefixos necessários. No projeto React (Vite), a lista de navegadores suportados foi definida em "browserslist" no package.json ("cover 99.5%") e conferida com o comando npx browserslist.',
                        ],
                    ],
                    'conclusion' => 'Construir o Currencee reforçou a importância de tratar a compatibilidade entre navegadores desde o início e de conhecer bem as ferramentas de build. Resolver questões como o suporte ao Safari e a configuração do Autoprefixer trouxe aprendizados valiosos sobre desenvolvimento web.',
                    'screenshots' => [],
                ],
            ],
            [
                'title' => 'FinTrack',
                'slug' => 'fintrack',
                'description' => 'Plataforma de gestão financeira pessoal inspirada em aplicações de organização financeira, desenvolvida com foco em arquitetura, experiência do usuário e evolução para recursos de Open Finance.',
                'image' => 'image/projetos/organizze/Dashboard.png',
                'technologies' => ['Laravel', 'PHP', 'Vue 3', 'Inertia.js', 'Tailwind CSS', 'PostgreSQL', 'Docker'],
                'featured' => false,
                'details' => $this->finTrackDetails(),
            ],
            [
                'title' => 'WorkFacilit',
                'slug' => 'workfacilit',
                'description' => 'API/backend desenvolvido em Laravel para gerenciamento de empresas e oportunidades de trabalho.',
                'image' => null,
                'technologies' => ['PHP', 'Laravel', 'PostgreSQL/MySQL', 'Eloquent', 'REST API'],
                'featured' => false,
                'details' => $this->workFacilitDetails(),
            ],
            [
                'title' => 'VigiaSUS',
                'slug' => 'vigiasus',
                'description' => 'Projeto de monitoramento e organização de dados públicos relacionados ao SUS, com arquitetura orientada a dados e ingestão de fontes públicas.',
                'image' => null,
                'technologies' => ['Java', 'Spring Boot', 'PostgreSQL', 'Neo4j', 'Docker', 'n8n'],
                'featured' => false,
                'details' => $this->vigiaSusDetails(),
            ],
            [
                'title' => 'PicPay Wallet',
                'slug' => 'picpay-wallet',
                'description' => 'Projeto backend relacionado a uma carteira digital.',
                'image' => null,
                'technologies' => ['Java', 'Spring Boot', 'API', 'PostgreSQL'],
                'featured' => false,
                'details' => $this->picPayWalletDetails(),
            ],
            [
                'title' => 'FutureData',
                'slug' => 'futuredata',
                'description' => 'Sistema de gestão para assistência técnica: ordens de serviço de ponta a ponta, portal do cliente e atendimento automatizado por WhatsApp com IA.',
                'image' => null,
                'technologies' => ['Laravel', 'PHP', 'Blade', 'Alpine.js', 'Tailwind CSS', 'MySQL', 'Redis', 'n8n'],
                'featured' => false,
                'demo_url' => 'https://futuredata.carlos0ff.dev/auth/entrar',
                'details' => $this->futureDataDetails(),
            ],
            [
                'title' => 'FADA',
                'slug' => 'fada',
                'description' => 'Gestão de ponto e jornada: painel administrativo e o PWA "Bate o Botão", de registro de ponto com reconhecimento facial, GPS e antifraude.',
                'image' => null,
                'technologies' => ['PHP', 'JavaScript', 'PWA', 'Node.js', 'TensorFlow.js', 'SQLite', 'MySQL'],
                'featured' => false,
                'details' => $this->fadaDetails(),
            ],
            [
                'title' => 'VulnShop',
                'slug' => 'vulnshop',
                'description' => 'Laboratório educacional de segurança web: um e-commerce intencionalmente vulnerável em PHP puro, com desafios guiados, código comentado e a versão corrigida de cada falha.',
                'image' => null,
                'technologies' => ['PHP', 'MySQL', 'Apache', 'Docker', 'PDO'],
                'featured' => false,
                'details' => $this->vulnShopDetails(),
            ],
            [
                'title' => 'Blog Livt',
                'slug' => 'blog-livt',
                'description' => 'Blog full-stack com Laravel e Vue via Inertia: publicação de posts e comentários com interface reativa, sem uma API REST separada.',
                'image' => null,
                'technologies' => ['Laravel', 'PHP', 'Inertia.js', 'Vue 3', 'Tailwind CSS', 'Vite'],
                'featured' => false,
                'details' => $this->blogLivtDetails(),
            ],
            [
                'title' => 'CRUD App',
                'slug' => 'crud-app',
                'description' => 'Base em Laravel para estudo de operações CRUD, montada com ambiente Docker (Laravel Sail) e MySQL.',
                'image' => null,
                'technologies' => ['Laravel', 'PHP', 'Docker', 'MySQL'],
                'featured' => false,
                'details' => $this->crudAppDetails(),
            ],
        ];

        $this->renameLegacyProject();

        foreach ($projects as $index => $project) {
            $existing = Project::withTrashed()->where('title', $project['title'])->first();

            if ($existing) {
                if (empty($existing->details) && ! empty($project['details'])) {
                    $existing->update(['details' => $project['details']]);
                }

                continue;
            }

            Project::create($project + [
                'github_url' => null,
                'demo_url' => null,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * O projeto entrou no portfólio como "Realizze" e passou a se chamar "FinTrack".
     */
    private function renameLegacyProject(): void
    {
        if (Project::where('title', 'FinTrack')->exists()) {
            return;
        }

        Project::where('title', 'Realizze')->first()?->update([
            'title' => 'FinTrack',
            'slug' => 'fintrack',
            'featured' => false,
        ]);
    }

    /**
     * Conteúdo levantado do código do projeto (README, composer.json, package.json, rotas,
     * controllers, migrations, docker compose e workflow n8n).
     *
     * @return array<string, mixed>
     */
    private function finTrackDetails(): array
    {
        return [
            'overview' => [
                'FinTrack é uma plataforma de gestão financeira pessoal pensada como um produto SaaS. O backend é Laravel e a interface é Vue 3 com TypeScript, ligados por Inertia.js, o que dispensa uma API REST separada para as telas do aplicativo.',
                'As telas cobrem o fluxo de um produto financeiro: dashboard, lançamentos, contas, cartões de crédito, relatórios, limites de gastos, conexão bancária (Open Finance), notificações, perfil, assinatura e suporte, além de landing page e blog público.',
            ],
            'features' => [
                'Login com validação e limite de tentativas por usuário e IP, além das telas de cadastro e de recuperação de senha.',
                'Telas do aplicativo em Vue 3 + TypeScript: dashboard, lançamentos, contas, cartões, limites de gastos, notificações, perfil, assinatura e suporte.',
                'Relatórios mensal, anual, por categoria e de receitas versus despesas.',
                'Tela de Conexão Bancária que representa o fluxo de Open Finance Brasil: instituições, modal de autorização em etapas e status por conta.',
                'Registro de despesas por WhatsApp: uma mensagem como "gastei R$ 35 no almoço" vira uma transação, via workflow n8n e endpoint da API do Laravel.',
                'Endpoint POST /api/n8n/webhook protegido por segredo em header, com limite de 30 requisições por minuto e validação do payload.',
                'Landing page, página de planos e blog público.',
            ],
            'sections' => [
                [
                    'title' => 'Arquitetura',
                    'paragraphs' => [
                        'FinTrack é um monólito Laravel com Inertia.js: os controllers devolvem páginas Vue diretamente, sem camada de API para a interface. A única API HTTP é a usada pela automação com n8n.',
                    ],
                    'items' => [
                        'Rotas separadas por contexto em routes/web (public, auth, app e admin), mais routes/api.php para a integração com o n8n.',
                        'Controllers do aplicativo organizados por módulo em app/Http/Controllers/App (Conta, Lançamento, Cartão de Crédito, Categorias, Relatório, Limites de Gastos, Perfil, Plano, Suporte, Conexão Bancária, Configurações).',
                        'Páginas Vue em resources/js/pages (App, Auth, Web e Blog) e componentes reutilizáveis em resources/js/Components (buttons, inputs, feedback e layout).',
                        'Ziggy e Laravel Wayfinder expõem as rotas do Laravel para o frontend.',
                        'Middleware próprio (EnsureN8nSecret) e rate limiter dedicado ("n8n") para a rota de integração.',
                    ],
                    'image' => 'image/projetos/fintrack/arquitetura.svg',
                    'image_alt' => 'Diagrama de arquitetura do FinTrack: navegador com Vue e Inertia, monólito Laravel, API dedicada ao n8n, Evolution API e WhatsApp, e bancos de dados.',
                    'image_caption' => 'Visão geral: navegador (Vue/Inertia) ↔ monólito Laravel; automação de lançamentos via n8n/WhatsApp; persistência em MySQL, SQLite, Redis e PostgreSQL.',
                ],
                [
                    'title' => 'Integração com n8n e WhatsApp',
                    'paragraphs' => [
                        'O workflow "WhatsApp — Registrar Despesa" está versionado no repositório (docker/n8n/workflows). Ele recebe a mensagem por webhook, interpreta o texto, chama a API do Laravel e responde ao usuário pelo WhatsApp.',
                    ],
                    'items' => [
                        'Webhook recebe a mensagem e um nó de código extrai o texto.',
                        'Outro nó de código identifica o valor (aceita formatos como "R$ 35", "35,90" e "25.90"), a descrição e uma categoria por palavras-chave, com "Outros" como padrão.',
                        'Se a mensagem foi reconhecida, o workflow chama POST /api/n8n/webhook no Laravel; caso contrário, responde com uma mensagem de formato inválido.',
                        'Sucesso, erro da API e formato inválido têm respostas distintas, tanto para o webhook quanto para o envio no WhatsApp.',
                        'No Docker Compose, o n8n roda ao lado de uma instância da Evolution API, usada para o envio das mensagens.',
                    ],
                ],
                [
                    'title' => 'Banco de dados e infraestrutura',
                    'paragraphs' => [
                        'O banco do aplicativo Laravel é SQLite por padrão (.env.example) e o Docker Compose provê um MySQL 8.4 para o ambiente com Sail. O PostgreSQL 16 do compose é o banco da Evolution API, o serviço de WhatsApp usado pela automação.',
                    ],
                    'items' => [
                        'Migrations de users, accounts (banco, tipo de conta, saldo), credit_cards (bandeira, limite, fatura) e transactions (tipo receita, despesa ou transferência, valor, data, categoria, pago).',
                        'Tabelas do blog: posts, categories, authors, tags, post_meta, media, newsletter_subscribers e social_accounts.',
                        'Docker Compose com aplicação (Sail), MySQL, PostgreSQL, Redis, Evolution API e n8n.',
                    ],
                ],
            ],
            'development' => [
                'O backend usa Laravel 13 (PHP 8.4 ou superior) e Inertia.js 2. O frontend usa Vue 3.5, TypeScript 5.9, Tailwind CSS 4 e Vite 7, com ícones Lucide. ESLint e Prettier cuidam da qualidade do código do frontend.',
                'O desenvolvimento seguiu a linha "interface primeiro": as telas e as rotas nomeadas de cada módulo foram estruturadas antes da persistência. Hoje a persistência real está no login, nas migrations e no fluxo de transações via n8n.',
            ],
            'challenges' => [
                [
                    'title' => 'Transformar texto livre em lançamento financeiro',
                    'problem' => 'Mensagens de WhatsApp como "paguei R$ 25,90 no café" não têm formato fixo, e o valor pode vir com ou sem "R$", com vírgula ou ponto.',
                    'solution' => 'Um nó de código no n8n extrai o valor por expressão regular, deriva a descrição do trecho após preposições como "no", "na" e "de", e classifica a categoria por palavras-chave. Mensagens que não seguem o padrão recebem uma resposta de formato inválido em vez de gerar um lançamento errado.',
                ],
                [
                    'title' => 'Proteger o endpoint chamado pela automação',
                    'problem' => 'A rota que cria transações é chamada por um serviço externo, sem sessão de usuário, e não pode ficar aberta.',
                    'solution' => 'O middleware EnsureN8nSecret compara o header X-N8N-Secret com o segredo configurado e responde 401 quando ele é inválido. A rota tem limite de 30 requisições por minuto, valida o payload (tipo, valor maior que zero, data no formato Y-m-d) e associa a transação a um usuário definido em configuração. Os testes de feature cobrem autenticação, validação e criação da transação.',
                ],
                [
                    'title' => 'Da interface à persistência',
                    'problem' => 'As telas foram construídas antes do backend de cada módulo. Nos controllers de contas, lançamentos, cartões e conexão bancária, as ações de escrita ainda estão marcadas como TODO, e as telas trabalham com dados de exemplo.',
                    'solution' => 'As migrations de accounts, credit_cards e transactions já existem, e o primeiro fluxo de escrita implementado é o registro de transações pela API. Conectar as demais ações dos controllers a esses modelos é o passo seguinte do projeto.',
                ],
            ],
            'conclusion' => 'FinTrack foi um exercício de arquitetura full-stack: monólito Laravel com Inertia e Vue, automação de entrada de dados por WhatsApp com n8n e modelagem inicial do domínio financeiro. O projeto segue em evolução: a interface está estruturada para receber a persistência dos módulos e a integração Open Finance ainda é uma representação em interface, sem conexão real com instituições.',
            'screenshots' => [
                [
                    'src' => 'image/projetos/organizze/Dashboard.png',
                    'alt' => 'Dashboard do FinTrack com resumo das contas e gráfico em rosca por categoria de gastos',
                    'caption' => 'Dashboard — resumo das contas e gastos por categoria.',
                ],
                [
                    'src' => 'image/projetos/organizze/lancamentos.png',
                    'alt' => 'Tela de lançamentos do FinTrack com a lista de receitas e despesas',
                    'caption' => 'Lançamentos — receitas e despesas do período.',
                ],
                [
                    'src' => 'image/projetos/organizze/contas.png',
                    'alt' => 'Tela de contas a pagar e receber com totais, abas e calendário de vencimentos',
                    'caption' => 'Contas a pagar e receber, com totais e vencimentos.',
                ],
                [
                    'src' => 'image/projetos/organizze/assinaturas.png',
                    'alt' => 'Tela de planos e assinatura do FinTrack',
                    'caption' => 'Planos e assinatura.',
                ],
                [
                    'src' => 'image/projetos/organizze/notificacoes.png',
                    'alt' => 'Central de notificações do FinTrack',
                    'caption' => 'Central de notificações.',
                ],
                [
                    'src' => 'image/projetos/organizze/configuracoes.png',
                    'alt' => 'Tela de configurações da conta no FinTrack',
                    'caption' => 'Configurações da conta.',
                ],
                [
                    'src' => 'image/projetos/organizze/suporte.png',
                    'alt' => 'Central de suporte do FinTrack',
                    'caption' => 'Suporte.',
                ],
                [
                    'src' => 'image/projetos/organizze/chatbot.png',
                    'alt' => 'Central de Ajuda e Suporte com perguntas frequentes e janela de chat',
                    'caption' => 'Central de ajuda com FAQ e chat de suporte.',
                ],
            ],
        ];
    }

    /**
     * O código-fonte do WorkFacilit não está disponível para análise; a página usa só a descrição
     * e a stack informadas no portfólio, sem inventar endpoints, modelagem ou desafios.
     *
     * @return array<string, mixed>
     */
    private function workFacilitDetails(): array
    {
        return [
            'overview' => [
                'WorkFacilit é uma API/backend desenvolvida em Laravel para o gerenciamento de empresas e oportunidades de trabalho.',
            ],
            'sections' => [
                [
                    'title' => 'Stack',
                    'paragraphs' => [
                        'O projeto é um backend em PHP com Laravel, que usa o Eloquent como ORM e um banco relacional (PostgreSQL ou MySQL) e é exposto como API REST.',
                    ],
                    'note' => 'Endpoints, modelagem do banco e decisões de arquitetura serão detalhados aqui quando a documentação do projeto for publicada.',
                ],
            ],
            'screenshots' => [],
        ];
    }

    /**
     * Conteúdo levantado do código e da documentação do projeto (README, pom.xml, application.properties,
     * docker-compose, serviços do backend e docs/n8n). O n8n aparece como proposta: ainda não há workflows.
     *
     * @return array<string, mixed>
     */
    private function vigiaSusDetails(): array
    {
        return [
            'overview' => [
                'VigiaSUS é uma ferramenta de vigilância cívica em saúde pública: cruza dados abertos do governo para apontar padrões suspeitos em estabelecimentos de saúde. Nasceu como TCC de Ciência da Computação e está em desenvolvimento.',
                'O backend, em Spring Boot, recebe o código CNES de um estabelecimento, consulta as fontes públicas, guarda o resultado em PostgreSQL, aplica sete regras de detecção e mantém um grafo de estabelecimentos no Neo4j. O frontend é uma aplicação Angular com grafo interativo, dashboard, busca e análise.',
            ],
            'features' => [
                'Análise de um estabelecimento pelo código CNES (GET /api/estabelecimentos/{codigoCnes}), que consolida cadastro, estoque de medicamentos, despesas federais e situação no TCU em uma única resposta.',
                'Cache em PostgreSQL com validade de 6 horas para os dados do CNES e do BNAFAR/HORUS; Portal da Transparência e TCU são sempre consultados ao vivo.',
                'Sete regras determinísticas de detecção, com alertas de severidade alta, média ou baixa.',
                'Endpoint de resumo do painel (GET /api/dashboard/stats) com totais de estabelecimentos e de alertas por severidade.',
                'Sincronização de cada estabelecimento analisado como nó no Neo4j, sem interromper a análise se o grafo estiver indisponível.',
                'Frontend Angular com landing page, dashboard, busca e página de análise, e grafo interativo com Cytoscape.js.',
                'Docker Compose com Neo4j, PostgreSQL, backend e frontend (Nginx), com healthchecks.',
            ],
            'sections' => [
                [
                    'title' => 'Arquitetura',
                    'paragraphs' => [
                        'O sistema tem três camadas: o frontend Angular, o backend Spring Boot e dois bancos com papéis diferentes.',
                    ],
                    'items' => [
                        'Frontend: Angular 21 com componentes standalone, Tailwind CSS 4 e Cytoscape.js, servido por Nginx, que encaminha /api/ ao backend.',
                        'Backend: Spring Boot 3.4 com Java 17, Spring Data JPA, Spring Data Neo4j e RestClient para as APIs externas, organizado em pacotes api, client, service, domain, repository, neo4j e config.',
                        'PostgreSQL 16: estabelecimentos, itens de estoque e alertas.',
                        'Neo4j 5 com o plugin APOC: grafo de estabelecimentos.',
                    ],
                    'note' => 'O grafo exibido no frontend usa dados de exemplo e as APIs abertas do DATASUS. O backend expõe apenas os endpoints de análise e de estatísticas, então o Neo4j ainda não alimenta essa tela.',
                ],
                [
                    'title' => 'Pipeline de dados',
                    'paragraphs' => [
                        'Hoje o pipeline é sob demanda: ele roda dentro da requisição de análise, e não em segundo plano. O fluxo abaixo corresponde ao EstabelecimentoService do backend.',
                    ],
                    'flow' => [
                        'steps' => [
                            ['label' => 'Fontes públicas', 'detail' => 'CNES e BNAFAR/HORUS (DATASUS), Portal da Transparência e certidões do TCU.'],
                            ['label' => 'Spring Boot: clientes das APIs', 'detail' => 'CNES e BNAFAR só são buscados quando o cache do PostgreSQL não existe ou passou de 6 horas. Transparência e TCU são consultados a cada análise.'],
                            ['label' => 'Processamento: detecção de alertas', 'detail' => 'O AlertaDetector aplica sete regras sobre os dados reunidos e classifica cada alerta por severidade.'],
                            ['label' => 'PostgreSQL', 'detail' => 'Persiste o estabelecimento, o estoque e os alertas.'],
                            ['label' => 'Neo4j', 'detail' => 'Grava ou atualiza o nó do estabelecimento (upsert por código CNES), sem bloquear a resposta em caso de falha.'],
                            ['label' => 'Dados organizados', 'detail' => 'A API devolve uma resposta única com cadastro, estoque, alertas, despesas e situação no TCU.'],
                        ],
                        'caption' => 'Fluxo implementado no código, do código CNES informado até a resposta da API.',
                    ],
                ],
                [
                    'title' => 'Ingestão de dados',
                    'paragraphs' => [
                        'A ingestão atual é pontual: cada análise chama as APIs públicas. Não há agendamento, filas nem armazenamento do payload bruto.',
                    ],
                    'items' => [
                        'CNES (DATASUS): dados cadastrais do estabelecimento, sem autenticação.',
                        'BNAFAR/HORUS (DATASUS): estoque de medicamentos por estabelecimento.',
                        'Portal da Transparência: despesas federais por favorecido (CNPJ), com chave de API configurada por variável de ambiente.',
                        'TCU (Certidões APF): consulta de inidoneidade por CNPJ.',
                        'O cliente do SIOPS existe no código, mas ainda não participa do fluxo principal.',
                    ],
                ],
                [
                    'title' => 'PostgreSQL',
                    'paragraphs' => [
                        'O PostgreSQL guarda o resultado das análises e funciona como cache dos dados do CNES e do BNAFAR/HORUS. O esquema é gerado pelo Hibernate a partir das entidades.',
                    ],
                    'items' => [
                        'Estabelecimento: cadastro normalizado do CNES, com data da última sincronização.',
                        'ItemEstoque: medicamentos por estabelecimento, com programa de saúde, quantidade e validade.',
                        'Alerta: severidade, título, detalhe e valor estimado, ligado ao estabelecimento.',
                        'As sete regras cobrem: CNPJ inidôneo no TCU, repasses federais acima do esperado para o tipo de unidade, tipo de unidade sem farmácia com estoque, unidade sem atendimento SUS com estoque do SUS, cadastro CNES desatualizado há mais de um ano, medicamentos sem programa declarado e estoque acima do esperado.',
                    ],
                ],
                [
                    'title' => 'Neo4j',
                    'paragraphs' => [
                        'O grafo hoje tem um único tipo de nó, Estabelecimento, com código CNES, nome, UF, município, CNPJ, atendimento SUS e total de alertas. Os relacionamentos previstos (mesmo CNPJ, localizado em, recebe medicamento) ainda não foram implementados.',
                    ],
                ],
                [
                    'title' => 'n8n',
                    'paragraphs' => [
                        'O n8n está previsto como orquestrador de ingestão, mas ainda não foi implementado: não há workflows nem serviço n8n no Docker Compose. O que existe é a proposta técnica documentada no repositório, resultado de uma auditoria da arquitetura atual e de uma pesquisa de APIs públicas.',
                    ],
                    'items' => [
                        'Princípio da proposta: o n8n coleta, normaliza, deduplica, valida e grava os dados; as regras de risco, o score e os alertas continuam no Spring Boot.',
                        'Tabelas propostas para o PostgreSQL: sources, sync_checkpoints, raw_source_data, execution_log e data_quality_issues, com idempotência por fonte, endpoint, identificador e hash.',
                        'Um workflow por fonte (Portal da Transparência, PNCP, TCU, Receita, DATASUS), com sincronização incremental por checkpoint.',
                        'Roadmap em 13 etapas, começando pelo conector do CNES.',
                    ],
                    'flow' => [
                        'steps' => [
                            ['label' => 'Fontes públicas', 'detail' => 'Portal da Transparência, PNCP, TCU, CGU, Receita e DATASUS.'],
                            ['label' => 'n8n', 'detail' => 'Coleta, normalização, deduplicação e validação, com checkpoints de sincronização.', 'status' => 'planned'],
                            ['label' => 'PostgreSQL', 'detail' => 'Payload bruto e entidades normalizadas (empresas, contratos, licitações, sanções).', 'status' => 'planned'],
                            ['label' => 'Spring Boot', 'detail' => 'Motor de análise. Hoje já executa as sete regras; indicadores e score explicável fazem parte da proposta.'],
                            ['label' => 'Neo4j', 'detail' => 'Grafo de entidades e relacionamentos.'],
                            ['label' => 'API REST e Angular', 'detail' => 'Dashboard, busca, grafo e alertas.'],
                        ],
                        'caption' => 'Arquitetura proposta em docs/n8n. As etapas tracejadas ainda não existem no código.',
                    ],
                ],
            ],
            'development' => [
                'O backend foi dividido por responsabilidade (controllers, clientes de API, serviços, entidades e repositórios), e todos os arquivos Java têm Javadoc. O frontend roda sozinho com dados de exemplo, o que facilita demonstrar o projeto sem subir os bancos.',
                'A documentação fica na pasta docs do repositório, organizada por área: API, arquitetura, backend, frontend, infraestrutura, n8n e TCC.',
            ],
            'challenges' => [
                [
                    'title' => 'Cruzar fontes com identificadores e formatos diferentes',
                    'problem' => 'Cada fonte pública identifica o estabelecimento à sua maneira: o CNES usa o código CNES, enquanto a Transparência e o TCU são consultados por CNPJ, e as datas chegam em formatos distintos.',
                    'solution' => 'O código CNES é o ponto de entrada, e o CNPJ retornado por ele alimenta as consultas seguintes. O detector de alertas aceita datas nos formatos yyyy-MM-dd, dd/MM/yyyy e yyyyMMdd.',
                ],
                [
                    'title' => 'Continuar funcionando quando uma fonte ou o grafo falha',
                    'problem' => 'As APIs públicas ficam indisponíveis com frequência, e o Neo4j é uma dependência auxiliar da análise.',
                    'solution' => 'Se a API do CNES falhar e o estabelecimento já estiver em cache, o cache é usado mesmo expirado. Uma consulta ao TCU indisponível vira um estado "indisponível" na resposta, e a gravação no Neo4j roda dentro de try/catch e apenas registra um aviso.',
                ],
                [
                    'title' => 'Não transformar heurística em acusação',
                    'problem' => 'Alertas baseados em regras podem ser lidos como prova de irregularidade, o que não é o caso.',
                    'solution' => 'Os limiares por tipo de unidade ficam explícitos no código, cada alerta traz título e detalhe, e o projeto declara que os padrões identificados indicam relações entre entidades e não constituem prova.',
                ],
            ],
            'conclusion' => 'O VigiaSUS reúne, em um único projeto, integração com APIs públicas, persistência relacional, grafo e uma interface interativa. O núcleo (consulta, cache, sete regras e resposta consolidada) já funciona de ponta a ponta. A ingestão automatizada via n8n, mais cruzamentos entre bases e algoritmos de detecção de comunidades no Neo4j seguem como próximos passos.',
            'screenshots' => [],
        ];
    }

    /**
     * O código-fonte do PicPay Wallet não está disponível para análise; a página usa só a descrição
     * e a stack informadas no portfólio, sem inventar endpoints, regras de negócio ou desafios.
     *
     * @return array<string, mixed>
     */
    private function picPayWalletDetails(): array
    {
        return [
            'overview' => [
                'PicPay Wallet é um projeto backend relacionado a uma carteira digital.',
            ],
            'sections' => [
                [
                    'title' => 'Stack',
                    'paragraphs' => [
                        'O backend é escrito em Java com Spring Boot, expõe uma API e usa PostgreSQL para persistência.',
                    ],
                    'note' => 'Endpoints, regras de negócio e decisões de arquitetura serão detalhados aqui quando a documentação do projeto for publicada.',
                ],
            ],
            'screenshots' => [],
        ];
    }

    /**
     * Conteúdo levantado do código e da documentação do projeto (README, composer.json, rotas,
     * controllers, migrations, docker compose e docs de WhatsApp/n8n).
     *
     * @return array<string, mixed>
     */
    private function futureDataDetails(): array
    {
        return [
            'overview' => [
                'FutureData é um sistema de gestão para assistência técnica de equipamentos. O backend é Laravel, com interface em Blade e Alpine.js, e cobre o ciclo completo de uma Ordem de Serviço (OS), da entrada à entrega.',
                'Além do painel interno, o sistema tem um portal para o cliente acompanhar o reparo e uma integração de WhatsApp que notifica o cliente a cada etapa e responde automaticamente com um agente de IA.',
            ],
            'features' => [
                'Ciclo completo da OS: checklist de entrada, diagnóstico, orçamento com aprovação do cliente, laudo técnico, assinaturas e entrega.',
                'Cadastro de clientes e dos equipamentos de cada cliente.',
                'Estoque de peças com controle de garantia.',
                'Caixa e vendas no balcão (PDV), módulo financeiro e relatórios.',
                'Catálogo de serviços e usuários com papéis e permissões.',
                'Portal do cliente com acesso por token, aprovação ou recusa do orçamento e leitura do laudo.',
                'Notificações de WhatsApp em cada etapa da OS e um assistente de IA que responde ao cliente automaticamente.',
            ],
            'sections' => [
                [
                    'title' => 'Arquitetura',
                    'paragraphs' => [
                        'FutureData é um monólito Laravel com views em Blade e interatividade em Alpine.js. As rotas são separadas por contexto e os controllers organizados por módulo.',
                    ],
                    'items' => [
                        'Rotas divididas em public, auth, app e painel, mais uma API interna para o n8n.',
                        'Controllers por módulo em app/Http/Controllers/App (Ordens, Clientes, Estoque, Caixa, Financeiro, Loja, Serviços, Relatórios, Usuários, Configurações) e um conjunto próprio para o Portal do cliente.',
                        'MySQL 8.4, Redis e Meilisearch provisionados via Laravel Sail; migrations versionadas cobrindo OS, clientes, equipamentos, peças, caixa, vendas, laudos e assinaturas.',
                    ],
                    'image' => 'image/projetos/futuredata/arquitetura.svg',
                    'image_alt' => 'Diagrama de arquitetura do FutureData: painel interno e portal do cliente em Blade/Alpine, monólito Laravel com controllers por módulo, API interna para o n8n, automação de OS com Evolution API, WhatsApp e agente de IA, e bancos MySQL, Redis e Meilisearch.',
                    'image_caption' => 'Visão geral: painel e portal do cliente (Blade/Alpine) ↔ monólito Laravel; eventos de OS disparam a automação via n8n (Evolution API/WhatsApp + agente de IA); persistência em MySQL, Redis e Meilisearch.',
                ],
                [
                    'title' => 'Portal do cliente',
                    'paragraphs' => [
                        'O cliente acessa o portal por um token ligado à OS e acompanha o andamento do reparo sem precisar de uma conta interna.',
                    ],
                    'items' => [
                        'Linha do tempo das etapas, do recebimento à entrega.',
                        'Orçamento com itens e botões de aprovar ou recusar, registrados na própria OS.',
                        'Laudo técnico e assinaturas por documento.',
                    ],
                ],
                [
                    'title' => 'Integração WhatsApp e n8n',
                    'paragraphs' => [
                        'Os eventos da OS saem do Laravel para um webhook do n8n, que encaminha as mensagens pela Evolution API até o WhatsApp do cliente. As mensagens recebidas são tratadas por um agente de IA no n8n.',
                    ],
                    'items' => [
                        'Eventos os.criada, os.status_alterado e os.orcamento_respondido disparam notificações ao cliente.',
                        'Uma API interna para o n8n (consulta de cliente) é protegida por token enviado em header.',
                        'O catálogo de mensagens de cada etapa e a configuração do n8n/Evolution estão documentados na pasta docs.',
                    ],
                ],
            ],
            'development' => [
                'O backend usa Laravel 13 (PHP 8.3 ou superior), com Tailwind CSS 4 e Vite no frontend. O SDK da Anthropic está configurado para o atendimento com IA, e o ambiente de desenvolvimento roda em Docker (Sail) com MySQL, Redis, Meilisearch e Mailpit.',
            ],
            'conclusion' => 'FutureData reúne, em um único produto, a operação de uma assistência técnica (OS, estoque, caixa e financeiro), um portal de acompanhamento para o cliente e automação de atendimento por WhatsApp com IA. O projeto segue em evolução, consolidando os módulos em torno do ciclo da Ordem de Serviço.',
            'screenshots' => [],
        ];
    }

    /**
     * Conteúdo levantado do código e da documentação do projeto (README, docs/ARQUITETURA.md,
     * docs/PONTO-PWA.md, composer.json, estrutura de app/Ponto e serviço Node de biometria).
     *
     * @return array<string, mixed>
     */
    private function fadaDetails(): array
    {
        return [
            'overview' => [
                'FADA é um sistema de gestão de ponto e jornada. Ele tem um painel administrativo e o PWA "Bate o Botão", que permite ao colaborador registrar o ponto com reconhecimento facial, localização por GPS e verificações antifraude.',
                'A aplicação é escrita em PHP puro no padrão front controller (um único ponto de entrada em public/index.php), e a análise facial fica em um serviço interno em Node, acessível apenas localmente.',
            ],
            'features' => [
                'PWA com relógio oficial do servidor, próxima batida, horas trabalhadas no dia e batidas em revisão.',
                'Captura facial com orientação de enquadramento e iluminação e etapas de desafio.',
                'Registro supervisionado como alternativa quando a biometria ou as permissões falham.',
                'Geolocalização: estimativa da distância até a área de trabalho no aparelho, com a validação oficial sempre no servidor.',
                'Espelho mensal de batidas, com horas por dia e filtros por status.',
                'Perfil com cadastro, atualização e revogação de consentimento da biometria (LGPD) e escolha de tema.',
                'Tela "Controle de Batidas" para gestor e admin, com API real e login; papéis colaborador < gestor < admin.',
            ],
            'sections' => [
                [
                    'title' => 'Arquitetura',
                    'paragraphs' => [
                        'Toda requisição entra por public/index.php, que carrega o bootstrap, monta as rotas, executa os middlewares e chama o controller. Apenas a pasta public/ fica exposta; código, configuração, banco e chaves ficam fora dela.',
                    ],
                    'items' => [
                        'Núcleo próprio (App, Router, Request, Response, View, Container) e o domínio sob o namespace Ponto.',
                        'Domínio dividido por responsabilidade: Identity (biometria), Location (geocerca), Antifraud (sinais de risco), Punch (batida), Audit e Admin.',
                        'Middlewares de HTTPS, sessão, CSRF e autenticação por papel; limiares de biometria e de batida em arquivos de configuração.',
                        'CLI bin/ponto para migrate, keygen, criação de usuários e de geocercas, verificação da auditoria e health check.',
                    ],
                ],
                [
                    'title' => 'Biometria e antifraude',
                    'paragraphs' => [
                        'O reconhecimento facial roda em um serviço Node separado, que escuta só em 127.0.0.1 e é autenticado por HMAC. O provedor biométrico é registrado em um único ponto do contêiner, o que permite trocá-lo sem mexer no resto da aplicação.',
                    ],
                    'items' => [
                        'Serviço Node com TensorFlow.js e face-api para detecção, landmarks e descritor facial.',
                        'Verificações de qualidade e de vivacidade (liveness) na captura.',
                        'Motor de risco que combina sinais de dispositivo e de localização; a decisão final é sempre do servidor.',
                    ],
                ],
                [
                    'title' => 'Segurança e LGPD',
                    'paragraphs' => [
                        'O projeto foi pensado com privacidade e integridade dos dados em mente, tratando o registro de ponto como informação sensível.',
                    ],
                    'items' => [
                        'Criptografia com libsodium e senhas em argon2id, com bloqueio após tentativas.',
                        'Log de auditoria append-only, encadeado por hash, com verificação pela CLI.',
                        'Gestão de consentimento da biometria e política de retenção de dados.',
                    ],
                ],
            ],
            'development' => [
                'A aplicação roda em PHP 8.1 ou superior, com SQLite no desenvolvimento e MySQL em produção, e migrations versionadas. Há testes de unidade e de integração HTTP no backend, além dos testes próprios do serviço de biometria em Node.',
            ],
            'conclusion' => 'FADA mostra um registro de ponto moderno construído sem framework de aplicação: front controller em PHP puro, PWA instalável, biometria facial em um serviço isolado e um conjunto de camadas de segurança e antifraude, sempre com a decisão final no servidor.',
            'screenshots' => [],
        ];
    }

    /**
     * Conteúdo levantado do README e do código do projeto. A página descreve o laboratório em alto
     * nível e seu propósito educacional; os guias de exploração e os payloads ficam no repositório.
     *
     * @return array<string, mixed>
     */
    private function vulnShopDetails(): array
    {
        return [
            'overview' => [
                'VulnShop é um laboratório educacional de segurança web: um e-commerce fictício, escrito em PHP puro, deliberadamente vulnerável para ensinar segurança ofensiva e defensiva.',
                'Cada falha vem com código comentado, um guia de exercício (objetivo, dica, impacto e correção recomendada) e a versão corrigida ao lado da versão vulnerável, para comparar o padrão inseguro com o seguro.',
            ],
            'features' => [
                'Vinte e oito vulnerabilidades organizadas em quatro níveis de dificuldade, do básico ao encadeamento de falhas.',
                'Lista interativa de desafios, cada um com objetivo, dica, impacto e correção.',
                'Implementações vulnerável e segura lado a lado para cada falha.',
                'Seeder com dados 100% fictícios (clientes, produtos, pedidos).',
                'Ambiente isolado em Docker, com um serviço interno simulado usado só em um dos exercícios.',
            ],
            'sections' => [
                [
                    'title' => 'Arquitetura',
                    'paragraphs' => [
                        'O VulnShop usa um MVC próprio em PHP puro, sem framework, para deixar o fluxo de cada requisição explícito no exercício.',
                    ],
                    'items' => [
                        'Front controller em public/index.php, com Router, camada de banco em PDO, sessão, controllers e models próprios.',
                        'Docker Compose com o serviço web (PHP e Apache), o MySQL e um serviço interno simulado para um dos desafios.',
                        'Migrations e seeders versionados, com dados fictícios para o laboratório.',
                    ],
                ],
                [
                    'title' => 'Temas abordados',
                    'paragraphs' => [
                        'Os desafios cobrem, em alto nível, as principais categorias de falhas de aplicações web, com dificuldade crescente.',
                    ],
                    'items' => [
                        'Validação de entrada e autenticação.',
                        'Injeção e tratamento inadequado de dados do usuário.',
                        'Controle de acesso e referência direta a objetos.',
                        'Upload inseguro e lógica de negócio.',
                        'Encadeamento de várias falhas nos níveis mais avançados.',
                    ],
                    'note' => 'Esta página descreve o laboratório em alto nível. Os guias detalhados e os payloads de cada exercício ficam no repositório, para uso exclusivamente educacional.',
                ],
                [
                    'title' => 'Segurança do laboratório',
                    'paragraphs' => [
                        'Por ser intencionalmente vulnerável, o projeto só deve rodar em ambiente local e isolado, e traz salvaguardas para evitar uso indevido.',
                    ],
                    'items' => [
                        'Execução restrita a 127.0.0.1 e bloqueada fora do ambiente local.',
                        'Apenas dados fictícios; sem números reais de cartão e com senhas em bcrypt mesmo no laboratório.',
                        'Banner de aviso em todas as páginas e exercício de SSRF limitado a um serviço simulado na rede Docker.',
                    ],
                ],
            ],
            'development' => [
                'O laboratório foi construído em PHP 8.2 com MySQL 8 e Apache, sobre Docker Compose. A proposta é didática: cada vulnerabilidade indica sua localização no código e acompanha uma versão corrigida que demonstra o padrão seguro.',
            ],
            'conclusion' => 'VulnShop é um material de estudo de segurança web: reúne, em uma aplicação única e isolada, exemplos práticos de falhas comuns e suas correções. O uso das técnicas demonstradas só é legítimo em ambientes próprios ou com autorização explícita.',
            'screenshots' => [],
        ];
    }

    /**
     * Conteúdo levantado do código do projeto (composer.json, migrations, controllers, páginas Vue).
     *
     * @return array<string, mixed>
     */
    private function blogLivtDetails(): array
    {
        return [
            'overview' => [
                'Blog Livt é um blog full-stack feito para praticar a stack Laravel + Inertia + Vue. O backend Laravel entrega páginas Vue diretamente pelo Inertia, sem uma API REST separada para a interface.',
                'As telas cobrem o fluxo de um blog: criar, editar e listar posts, ler um post e comentar.',
            ],
            'features' => [
                'CRUD de posts, com título, slug, resumo e conteúdo, ligado a um autor.',
                'Listagem e leitura pública dos posts.',
                'Comentários com nome, e-mail e mensagem.',
                'Interface reativa com Inertia e Vue 3, sem recarregar a página.',
            ],
            'sections' => [
                [
                    'title' => 'Arquitetura',
                    'paragraphs' => [
                        'Monólito Laravel com Inertia: os controllers devolvem páginas Vue diretamente. As páginas ficam em resources/js/Pages/Posts e o estilo usa Tailwind CSS com Vite.',
                    ],
                    'items' => [
                        'Modelos Post e Comment, com exclusão em cascata do post para os comentários.',
                        'PostController como resource e um endpoint para gravar comentários.',
                        'Laravel 13, Inertia 3, Vue 3.5, Tailwind CSS 4 e Vite.',
                    ],
                ],
            ],
            'conclusion' => 'Blog Livt é um projeto de estudo que exercita o padrão monólito com Inertia: a produtividade do Laravel no backend com a reatividade do Vue no frontend, sem o custo de manter uma API separada.',
            'screenshots' => [],
        ];
    }

    /**
     * O CRUD App está no estágio inicial: o repositório tem o ambiente montado sobre a estrutura
     * padrão do Laravel, sem entidades de CRUD implementadas ainda. A página não inventa recursos.
     *
     * @return array<string, mixed>
     */
    private function crudAppDetails(): array
    {
        return [
            'overview' => [
                'CRUD App é uma base em Laravel usada para estudar operações de CRUD. O projeto está no estágio inicial: o ambiente (Laravel 13, Docker/Sail e MySQL) está montado e a aplicação roda sobre a estrutura padrão do framework.',
            ],
            'sections' => [
                [
                    'title' => 'Stack',
                    'paragraphs' => [
                        'Backend em PHP com Laravel 13, servido via Docker (Laravel Sail) com MySQL.',
                    ],
                    'note' => 'As entidades, telas e endpoints do CRUD serão documentados aqui conforme forem implementados.',
                ],
            ],
            'screenshots' => [],
        ];
    }
}
